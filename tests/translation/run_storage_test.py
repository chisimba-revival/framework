#!/usr/bin/env python3
"""Disposable MariaDB fixture, internal network, no published ports or site writes."""
import subprocess,secrets,time,pathlib
root=pathlib.Path(__file__).resolve().parents[2];network='chisimba-translation-fixture-'+secrets.token_hex(4);container=network+'-db';password=secrets.token_hex(24)
def run(args,**kw):
 result=subprocess.run(args,**kw)
 if result.returncode:raise RuntimeError('Isolated fixture command failed; see diagnostics above')
 return result
run(['docker','network','create','--internal',network],stdout=subprocess.DEVNULL)
try:
 run(['docker','run','--rm','-d','--name',container,'--network',network,'--network-alias','translation-db','--tmpfs','/var/lib/mysql','-e','MARIADB_ROOT_PASSWORD='+password,'-e','MARIADB_DATABASE=translation_fixture','mariadb:10.11.18'],stdout=subprocess.DEVNULL)
 for n in range(45):
  if subprocess.run(['docker','exec',container,'mariadb','-uroot','-p'+password,'-e','SELECT 1'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0:break
  time.sleep(1)
 else:raise RuntimeError('Fixture DB unavailable')
 run(['docker','run','--rm','--network',network,'--entrypoint','php','-e','TRANSLATION_FIXTURE=1','-e','TRANSLATION_FIXTURE_PASSWORD='+password,'-v',str(root)+':/framework:ro','chisimba-php85-web','-d','display_errors=1','-d','auto_prepend_file=','/framework/tests/translation/storage_test.php','/framework/app'])
finally:
 subprocess.run(['docker','stop',container],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
 subprocess.run(['docker','network','rm',network],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
