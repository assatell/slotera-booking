#!/usr/bin/env python3
"""Build an unsigned, cross-host deterministic Slotera release-candidate ZIP."""
from __future__ import annotations
import argparse, hashlib, json, os, pathlib, subprocess, sys, time
sys.dont_write_bytecode = True
from deterministic_zip import write_zip

ROOT = pathlib.Path(__file__).resolve().parents[1]
ARCHIVE_ROOT = 'slotera-booking/'
TEXT_EXTENSIONS = {
    '.css', '.html', '.ini', '.js', '.json', '.md', '.mjs', '.php', '.po',
    '.pot', '.sql', '.svg', '.txt', '.xml', '.yaml', '.yml',
}
TEXT_FILENAMES = {'CHANGELOG', 'LICENSE', 'README'}


def archive_exclusions() -> tuple[str, ...]:
    manifest = json.loads((ROOT / 'release-manifest.json').read_text(encoding='utf-8'))
    return tuple(str(item).replace('\\', '/').lstrip('./') for item in manifest.get('archive', {}).get('exclude', []))


def is_excluded(relative: str, exclusions: tuple[str, ...]) -> bool:
    for item in exclusions:
        if item.endswith('/') and relative.startswith(item):
            return True
        if relative == item:
            return True
    return False


def canonical_file_bytes(path: pathlib.Path) -> bytes:
    raw = path.read_bytes()
    if path.suffix.lower() in TEXT_EXTENSIONS or path.name in TEXT_FILENAMES:
        return raw.replace(b'\r\n', b'\n').replace(b'\r', b'\n')
    return raw


def git(args: list[str]) -> str:
    try:
        run = subprocess.run(
            ['git', *args],
            cwd=ROOT,
            check=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.DEVNULL,
            text=True,
        )
        return run.stdout.strip()
    except (OSError, subprocess.CalledProcessError):
        return ''


def capture_vcs(env: dict[str, str]) -> None:
    required = env.get('SLTR_VCS_REQUIRED', '').strip() == '1'
    expected_tag = env.get('SLTR_VCS_TAG', '').strip()
    commit = git(['rev-parse', 'HEAD'])

    if not commit:
        if required:
            raise SystemExit('VCS-bound build requires Git metadata and an exact HEAD commit')
        env['SLTR_VCS_COMMIT'] = ''
        env['SLTR_VCS_TAG'] = ''
        env['SLTR_VCS_DIRTY'] = ''
        env['SLTR_VCS_STATE'] = 'source-archive'
        return

    dirty = git(['status', '--porcelain']) != ''
    tag = git(['describe', '--tags', '--exact-match', 'HEAD'])

    if required:
        if dirty:
            raise SystemExit('VCS-bound build requires a clean working tree')
        if not expected_tag:
            raise SystemExit('VCS-bound build requires SLTR_VCS_TAG')
        if tag != expected_tag:
            raise SystemExit(f'VCS-bound build tag mismatch: expected {expected_tag}, HEAD tag is {tag or "<none>"}')

    env['SLTR_VCS_COMMIT'] = commit
    env['SLTR_VCS_TAG'] = tag
    env['SLTR_VCS_DIRTY'] = '1' if dirty else '0'
    env['SLTR_VCS_STATE'] = 'git-dirty' if dirty else 'git-clean'


def canonical_files() -> list[tuple[bytes, str, bytes]]:
    files: list[tuple[bytes, str, bytes]] = []
    exclusions = archive_exclusions()
    for path in ROOT.rglob('*'):
        if not path.is_file() or '.git' in path.parts or 'node_modules' in path.parts or '__pycache__' in path.parts or path.suffix == '.pyc':
            continue
        rel = path.relative_to(ROOT).as_posix()
        if is_excluded(rel, exclusions):
            continue
        if path.suffix.lower() == '.zip':
            raise SystemExit(f'nested ZIP is not allowed in release source tree: {rel}')
        files.append((rel.encode('utf-8'), rel, canonical_file_bytes(path)))
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
    capture_vcs(env)
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
