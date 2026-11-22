#!/usr/bin/env python3
"""Retain a site's live and preceding verified release; dry-run unless --apply.

Deployment records and a recent complete source capture are required. Never
remove backups, staging, shared content, mounted releases or newer candidates.
"""
import argparse
from contextlib import ExitStack
import fcntl
import json
from pathlib import Path
import shutil
import subprocess
import time


def release_root(path, releases):
    path = Path(path).resolve(strict=True)
    if path.name != 'ch' or path.parent.parent != releases:
        raise RuntimeError('Release must be a direct releases/<name>/ch directory')
    return path.parent


def plan(site, key, mounted, now=None):
    site = Path(site).resolve(strict=True)
    releases = site / 'releases'
    if releases.is_symlink():
        raise RuntimeError('Release directory must not be a symlink')
    link = site / 'app/current'
    if not link.is_symlink():
        raise RuntimeError('Current release must be an explicit symlink')
    current = link.resolve(strict=True)
    records = []
    for path in site.glob('*/' + key + '/deployed.json'):
        record = json.loads(path.read_text())
        if record.get('release') == str(current) and record.get('status') == 'online-verified':
            records.append(record)
    if len(records) != 1:
        raise RuntimeError('One verified deployment record is required for the live release')
    record = records[0]
    previous = Path(record['previous']).resolve(strict=True)
    if not Path(record['backup']).is_dir():
        raise RuntimeError('Live deployment backup is missing')
    prior = [json.loads(p.read_text()) for p in site.glob('*/' + key + '/deployed.json')]
    if not any(r.get('release') == str(previous) and r.get('status') == 'online-verified' for r in prior):
        raise RuntimeError('Preceding release is not verified')
    keep = {release_root(current, releases), release_root(previous, releases)}
    if len(keep) != 2:
        raise RuntimeError('Two distinct verified releases are required')
    capture = json.loads((site / 'backups/automated/ready/manifest.json').read_text())
    age = (time.time() if now is None else now) - capture['captured_at']
    required = {'database.sql', 'code.tar', 'content.tar', 'operations.tar', 'images.tar',
                'containers.json', 'database-checksums.tsv', 'worker-schedules.json'}
    if (capture.get('format') != 1 or capture.get('code_path') != str(current)
            or not 0 <= age <= 36 * 3600 or not required.issubset(capture.get('files', {}))):
        raise RuntimeError('A recent complete capture of the current release is required')
    # Includes stopped containers: they may still be intended for restart.
    protected = [Path(p).resolve() for p in mounted]
    protected += [p.resolve() for p in (site / 'app').iterdir() if p.is_symlink()]
    candidates = []
    for path in sorted(releases.iterdir()):
        if path.is_symlink() or not path.is_dir():
            raise RuntimeError('Unexpected entry in releases: ' + path.name)
        if path in keep:
            continue
        if path.stat().st_mtime > max(p.stat().st_mtime for p in keep):
            raise RuntimeError('Newer candidate release requires review: ' + path.name)
        if any(p == path or p.is_relative_to(path) or path.is_relative_to(p) for p in protected):
            raise RuntimeError('Another release is still referenced: ' + path.name)
        candidates.append(path)
    return {'keep': [str(p) for p in sorted(keep)], 'remove': [str(p) for p in candidates]}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--site', required=True)
    parser.add_argument('--key', required=True)
    parser.add_argument('--apply', action='store_true')
    args = parser.parse_args()
    if not args.key.isalnum():
        raise RuntimeError('Invalid site key')
    site = Path(args.site).resolve(strict=True)
    with ExitStack() as stack:
        for path in [site / 'shared-release.lock', site / 'backups/automated/capture.lock']:
            handle = stack.enter_context(path.open('r'))
            fcntl.flock(handle, fcntl.LOCK_EX | fcntl.LOCK_NB)
        ids = subprocess.check_output(['docker', 'ps', '-aq'], text=True).split()
        if not ids:
            raise RuntimeError('Container inventory is empty')
        containers = json.loads(subprocess.check_output(['docker', 'inspect', *ids]))
        mounted = [m['Source'] for c in containers for m in c['Mounts']]
        result = plan(site, args.key, mounted)
        result.update(applied=False, time=int(time.time()), free_before=shutil.disk_usage(site).free)
        if args.apply:
            log = site / 'logs' / ('release-retention-' + time.strftime('%Y%m%d-%H%M%S') + '.json')
            log.write_text(json.dumps(result, indent=2)); log.chmod(0o600)
            for path in result['remove']:
                shutil.rmtree(path)
            result.update(applied=True, free_after=shutil.disk_usage(site).free)
            log.write_text(json.dumps(result, indent=2))
        print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
