"""Small deterministic ZIP writer using an in-tree RFC1951 fixed-Huffman DEFLATE encoder.

No host zlib compressor is used. The byte stream is defined by this module and the
canonical input order supplied by the release builder.
"""
from __future__ import annotations
import binascii, pathlib, struct

# RFC 1951 length and distance tables.
_LENGTH_BASE = [3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258]
_LENGTH_EXTRA = [0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0]
_DIST_BASE = [1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577]
_DIST_EXTRA = [0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13]

class _Bits:
    __slots__ = ('out','buf','count')
    def __init__(self): self.out=bytearray(); self.buf=0; self.count=0
    def put(self, value:int, n:int):
        self.buf |= (value & ((1<<n)-1)) << self.count
        self.count += n
        while self.count >= 8:
            self.out.append(self.buf & 0xff); self.buf >>= 8; self.count -= 8
    def finish(self)->bytes:
        if self.count: self.out.append(self.buf & 0xff)
        return bytes(self.out)

def _rev(v:int,n:int)->int:
    r=0
    for _ in range(n): r=(r<<1)|(v&1); v >>= 1
    return r

def _fixed_lit(sym:int):
    if sym <= 143: return _rev(0x30 + sym, 8), 8
    if sym <= 255: return _rev(0x190 + (sym-144), 9), 9
    if sym <= 279: return _rev(sym-256, 7), 7
    return _rev(0xC0 + (sym-280), 8), 8

def _emit_sym(bits:_Bits, sym:int):
    code,n = _fixed_lit(sym); bits.put(code,n)

def _length_code(length:int):
    for i,base in enumerate(_LENGTH_BASE):
        extra=_LENGTH_EXTRA[i]; top=base + ((1<<extra)-1 if extra else 0)
        if length <= top: return 257+i, extra, length-base
    raise ValueError(length)

def _dist_code(dist:int):
    for i,base in enumerate(_DIST_BASE):
        extra=_DIST_EXTRA[i]; top=base + ((1<<extra)-1 if extra else 0)
        if dist <= top: return i, extra, dist-base
    raise ValueError(dist)

def deflate_fixed(data:bytes)->bytes:
    """Deterministic single-block fixed-Huffman DEFLATE with fast greedy LZ77.

    The match finder intentionally uses only the most recent position for each
    three-byte key. This keeps build time linear and host-independent while
    retaining strong compression for source/text release payloads.
    """
    b=_Bits(); b.put(1,1); b.put(1,2)  # BFINAL=1, BTYPE=01 fixed Huffman
    n=len(data); i=0; last={}
    while i<n:
        best_len=0; best_dist=0
        if i+2<n:
            key=(data[i]<<16)|(data[i+1]<<8)|data[i+2]
            pos=last.get(key)
            if pos is not None:
                dist=i-pos
                if 0 < dist <= 32768:
                    max_len=min(258,n-i); ln=3
                    while ln<max_len and data[pos+ln]==data[i+ln]: ln += 1
                    best_len=ln; best_dist=dist
        if best_len>=3:
            sym,ex,val=_length_code(best_len); _emit_sym(b,sym); b.put(val,ex)
            dsym,dex,dval=_dist_code(best_dist); b.put(_rev(dsym,5),5); b.put(dval,dex)
            end=i+best_len
            while i<end:
                if i+2<n:
                    k=(data[i]<<16)|(data[i+1]<<8)|data[i+2]; last[k]=i
                i+=1
        else:
            _emit_sym(b,data[i])
            if i+2<n:
                key=(data[i]<<16)|(data[i+1]<<8)|data[i+2]; last[key]=i
            i+=1
    _emit_sym(b,256)
    return b.finish()

def _dos_datetime(dt):
    y,m,d,hh,mm,ss = dt
    y=max(1980,min(2107,y))
    return ((y-1980)<<9)|(m<<5)|d, (hh<<11)|(mm<<5)|(ss//2)

def write_zip(output:pathlib.Path, entries, dt):
    """Write canonical ZIP. entries iterable of (archive_name:str, source:pathlib.Path)."""
    date,time = _dos_datetime(dt)
    central=[]
    with output.open('wb') as fh:
        for name,src in entries:
            raw=src.read_bytes(); comp=deflate_fixed(raw); crc=binascii.crc32(raw)&0xffffffff
            name_b=name.encode('utf-8'); offset=fh.tell(); flags=0x0800; method=8
            local=struct.pack('<IHHHHHIIIHH',0x04034b50,20,flags,method,time,date,crc,len(comp),len(raw),len(name_b),0)
            fh.write(local); fh.write(name_b); fh.write(comp)
            central.append((name_b,crc,len(comp),len(raw),offset,flags,method))
        cd_start=fh.tell()
        for name_b,crc,cs,us,offset,flags,method in central:
            made_by=(3<<8)|20; ext=(0o100644<<16)
            hdr=struct.pack('<IHHHHHHIIIHHHHHII',0x02014b50,made_by,20,flags,method,time,date,crc,cs,us,len(name_b),0,0,0,0,ext,offset)
            fh.write(hdr); fh.write(name_b)
        cd_size=fh.tell()-cd_start
        count=len(central)
        if count>0xffff or cd_start>0xffffffff or cd_size>0xffffffff: raise ValueError('ZIP64 not supported by canonical writer')
        fh.write(struct.pack('<IHHHHIIH',0x06054b50,0,0,count,count,cd_size,cd_start,0))
