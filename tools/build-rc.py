#!/usr/bin/env python3
"""Build an unsigned, cross-host deterministic Slotera release-candidate ZIP."""
from __future__ import annotations
import argparse, hashlib, os, pathlib, subprocess, sys, time
sys.dont_write_bytecode = True
from deterministic_zip import write_zip

ROOT = pathlib.Path(__file__).resolve().parents[1]
ARCHIVE_ROOT = 'slotera-booking/'


def canonical_files() -> list[tuple[bytes, str, pathlib.Path]]:
    files: list[tuple[bytes, str, pathlib.Path]] = []
    for path in ROOT.rglob('*'):
        if not path.is_file() or '.git' in path.parts or 'node_modules' in path.parts or '__pycache__' in path.parts or path.suffix == '.pyc':
            continue
        rel = path.relative_to(ROOT).as_posix()
        files.append((rel.encode('utf-8'), rel, path))
    files.sort(key=lambda item: item[0])
    return files


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--output', required=True)
    ap.add_argument('--source-date-epoch', required=True, type=int)
    args = ap.parse_args()
    output = pathlib.Path(args.output).resolve()
    if ROOT in output.parents:
        raise SystemExit('output must be outside plugin root')
    if output.exists():
        raise SystemExit(f'output already exists: {output}')
    output.parent.mkdir(parents=True, exist_ok=True)

    env = os.environ.copy()
    env['SOURCE_DATE_EPOCH'] = str(args.source_date_epoch)
    env['SLTR_BUILD_OUTPUT'] = output.name
    env.setdefault('SLTR_BUILD_COMMAND', f"node tools/build-rc.mjs --output ../{output.name} --source-date-epoch {args.source_date_epoch}")
    env['SLTR_SIGNING_STATUS'] = env.get('SLTR_SIGNING_STATUS', 'not-performed-release-candidate')
    subprocess.run(['node', 'tools/release-metadata.mjs', 'prepare'], cwd=ROOT, env=env, check=True)

    dt = time.gmtime(args.source_date_epoch)[:6]
    # Canonical archive bytes are produced by the in-tree fixed-Huffman DEFLATE
    # encoder. No host zlib compressor is used, so Windows/Linux builds share the
    # same compression implementation as well as the same entry order/metadata.
    entries = [(f'{ARCHIVE_ROOT}{rel}', file) for _, rel, file in canonical_files()]
    write_zip(output, entries, dt)

    size = output.stat().st_size
    max_size = 8 * 1024 * 1024
    if size >= max_size:
        output.unlink(missing_ok=True)
        raise SystemExit(f'release ZIP exceeds strict <8 MiB gate: {size} bytes')

    digest = hashlib.sha256(output.read_bytes()).hexdigest()
    payload = f'ARCHIVE={output.name}\nSHA256={digest}\n'.encode('utf-8')
    if hasattr(sys.stdout, 'buffer'):
        sys.stdout.buffer.write(payload)
        sys.stdout.buffer.flush()
    else:
        sys.stdout.write(payload.decode('utf-8'))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
