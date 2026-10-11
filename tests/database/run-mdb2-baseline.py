#!/usr/bin/env python3
"""Run dbTable MDB2 baseline in disposable containers, never the installed database.

Usage: python3 tests/database/run-mdb2-baseline.py [local-image-name]
The fixture uses an internal Docker network, no published ports and tmpfs data.
"""
import pathlib
import secrets
import subprocess
import sys
import time

image = sys.argv[1] if len(sys.argv) > 1 else 'chisimba-php85-web'
name = 'chisimba-db-baseline-' + secrets.token_hex(6)
password = secrets.token_hex(24)
root = pathlib.Path(__file__).resolve().parents[2]

def run(args, **options):
    result = subprocess.run(args, **options)
    if result.returncode:
        raise RuntimeError('Disposable database check failed; see PHP diagnostics above')
    return result

run(['docker', 'network', 'create', '--internal', name], stdout=subprocess.DEVNULL)
try:
    run(['docker', 'run', '--rm', '-d', '--name', name, '--network', name,
         '--network-alias', 'baseline-db', '--tmpfs', '/var/lib/mysql',
         '-e', 'MARIADB_ROOT_PASSWORD=' + password,
         '-e', 'MARIADB_DATABASE=contract_fixture', 'mariadb:10.11.18'],
        stdout=subprocess.DEVNULL)
    for attempt in range(60):
        result = subprocess.run(['docker', 'exec', name, 'mariadb', '-uroot',
                                 '-p' + password, '-e', 'SELECT 1'],
                                stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        if result.returncode == 0:
            break
        time.sleep(1)
    else:
        raise RuntimeError('Disposable MariaDB failed to become ready')
    run(['docker', 'run', '--rm', '--network', name, '--entrypoint', 'php',
         '-e', 'CHISIMBA_DB_BASELINE=1', '-e', 'CHISIMBA_DB_PASSWORD=' + password,
         '-v', str(root) + ':/framework:ro', '-v', str(root.parent / 'modules') + ':/modules:ro', image,
         '-d', 'display_errors=1', '-d', 'auto_prepend_file=', '/framework/tests/database/mdb2_contract_baseline.php'])
finally:
    subprocess.run(['docker', 'rm', '-f', name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    subprocess.run(['docker', 'network', 'rm', name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
