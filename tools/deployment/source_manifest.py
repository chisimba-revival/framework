#!/usr/bin/env python3
"""Build from published Git objects and verify the complete shared runtime source.

No working-tree file is used as deployment input. Configuration and content live
outside this boundary. Private additions require an explicit per-path hash map.
"""
import argparse
import hashlib
import io
import json
from pathlib import Path, PurePosixPath
import subprocess
import tarfile

SOURCE_DIRS = ('classes', 'core_modules', 'skins', 'lib', 'cron', 'iconthemes', 'installer', 'debian', 'packages')
ROOT_FILES = ('index.php', 'gateway.php', '_htaccess', '_robots.txt', 'xd_receiver.htm', 'favicon.ico')


def digest(data):
    return hashlib.sha256(data).hexdigest()


def safe_path(path):
    p = PurePosixPath(path)
    if p.is_absolute() or '..' in p.parts or not p.parts:
        raise ValueError('Unsafe source path: ' + path)
    return path


def source_path(path):
    return path in ROOT_FILES or path.split('/')[0] in SOURCE_DIRS


def generated(path):
    return path.startswith('lib/HTMLPurifier/DefinitionCache/Serializer/') and path.endswith('.ser')


def git(repo, *args):
    return subprocess.check_output(['git', '-C', str(repo), *args])


def build(framework, modules, ref, destination):
    destination = Path(destination)
    destination.mkdir(parents=True, exist_ok=False)
    files, pins = {}, {}
    with tarfile.open(destination / 'source.tar.gz', 'w:gz') as bundle:
        for name, repo in [('framework', framework), ('modules', modules)]:
            # Require the actual remote branch tip, not just a stale origin ref.
            remote = git(repo, 'ls-remote', '--exit-code', 'origin', 'refs/heads/' + ref).decode().split()[0]
            local = git(repo, 'rev-parse', 'HEAD').decode().strip()
            if local != remote:
                raise RuntimeError(name + ': HEAD has not been pushed to the common branch')
            pins[name] = {'commit': local, 'branch': ref, 'remote': git(repo, 'remote', 'get-url', 'origin').decode().strip()}
            archive = git(repo, 'archive', '--format=tar', local)
            with tarfile.open(fileobj=io.BytesIO(archive)) as source:
                for member in source:
                    if not member.isfile():
                        if member.issym() or member.islnk():
                            raise RuntimeError('Source symlinks require explicit review: ' + member.name)
                        continue
                    if name == 'framework':
                        if not member.name.startswith('app/'):
                            continue
                        path = member.name[4:]
                        if not source_path(path):
                            continue
                    else:
                        path = 'packages/' + member.name
                    safe_path(path)
                    if path.startswith(('packages/hairplanner/', 'packages/imageedit-service/')) or 'hairplannerendpoints_trait' in path:
                        raise RuntimeError('Private source must not enter the shared bundle')
                    data = source.extractfile(member).read()
                    files[path] = digest(data)
                    entry = tarfile.TarInfo(path)
                    entry.size = len(data)
                    entry.mode = 0o644
                    bundle.addfile(entry, io.BytesIO(data))
    manifest = {'schema': 1, 'pins': pins, 'files': dict(sorted(files.items()))}
    raw = (json.dumps(manifest, sort_keys=True, indent=2) + '\n').encode()
    (destination / 'manifest.json').write_bytes(raw)
    (destination / 'manifest.sha256').write_text(digest(raw) + '\n')
    print(json.dumps({'files': len(files), 'manifest': digest(raw), 'pins': pins}))


def verify(root, manifest, overlay=None):
    root = Path(root)
    expected = dict(manifest['files'])
    for path, sha in (overlay or {}).items():
        safe_path(path)
        if not source_path(path) or len(sha) != 64:
            raise ValueError('Invalid private overlay entry')
        expected[path] = sha
    issues, actual = [], set()
    for name in SOURCE_DIRS:
        base = root / name
        if base.is_symlink():
            issues.append('symlink directory: ' + name)
            continue
        if base.exists():
            for p in base.rglob('*'):
                path = p.relative_to(root).as_posix()
                if p.is_symlink():
                    issues.append('symlink: ' + path)
                elif p.is_file() and not generated(path):
                    actual.add(path)
    for name in ROOT_FILES:
        if (root / name).is_file():
            actual.add(name)
    # Also reject extra root executables, which would bypass a directory-only scan.
    for p in root.iterdir():
        if p.is_file() and p.suffix in ('.php', '.inc', '.phtml'):
            actual.add(p.name)
    for path in sorted(actual - expected.keys()):
        issues.append('unexpected: ' + path)
    for path, sha in sorted(expected.items()):
        p = root / safe_path(path)
        if not p.is_file() or p.is_symlink():
            issues.append('missing/nonregular: ' + path)
        elif digest(p.read_bytes()) != sha:
            issues.append('changed: ' + path)
    return issues


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command', required=True)
    b = sub.add_parser('build')
    b.add_argument('--framework', required=True)
    b.add_argument('--modules', required=True)
    b.add_argument('--branch', default='release/consolidation-20260930')
    b.add_argument('--output', required=True)
    v = sub.add_parser('verify')
    v.add_argument('--root', required=True)
    v.add_argument('--manifest', required=True)
    v.add_argument('--manifest-sha256', required=True)
    v.add_argument('--private-overlay')
    args = parser.parse_args()
    if args.command == 'build':
        build(args.framework, args.modules, args.branch, args.output)
    else:
        raw = Path(args.manifest).read_bytes()
        if digest(raw) != args.manifest_sha256:
            raise SystemExit('Manifest differs from the approved common development revision')
        overlay = json.loads(Path(args.private_overlay).read_text()) if args.private_overlay else None
        issues = verify(args.root, json.loads(raw), overlay)
        print(json.dumps({'ok': not issues, 'issues': issues}, indent=2))
        raise SystemExit(1 if issues else 0)


if __name__ == '__main__':
    main()
