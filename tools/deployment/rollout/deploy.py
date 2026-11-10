"""Deploy an individually rehearsed shared code release; stop on failure without restoring data."""
from common import *
import fcntl,datetime
baseline=json.loads((work/'baseline.json').read_text());prepared=json.loads((work/'prepared.json').read_text());previous=current.resolve()
verify_source(work/'stage')
verify_baseline(previous)
assert str(previous)==baseline['code'];assert prepared['manifest']==sha(B/'manifest.json')
assert json.loads((work/'protected-before.json').read_text())==json.loads((work/'protected-after.json').read_text())
assert not (work/'deployed.json').exists()
if (work/'effective-manifest.json').exists():
 assert json.loads((work/'effective-manifest.json').read_text())==manifest,'Effective manifest drift'
 if prepared.get('effectiveManifest'):assert prepared['effectiveManifest']==sha(work/'effective-manifest.json')
i=inspect(web);assert i['Image']==baseline['imageId']==prepared['imageId'];image=i['Image']
for r in baseline['files']:assert sha(previous/r['path'])==r['before'],'Live drift '+r['path']
for r in manifest:assert sha(work/'stage'/r['path'])==r['after'],'Stage drift '+r['path']
networks=set(i['NetworkSettings']['Networks'])&set(inspect(db)['NetworkSettings']['Networks']);assert len(networks)==1;network=networks.pop()
stamp=datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%d-%H%M%S');backup=site/'backups'/('shared-'+stamp);release=site/'releases'/('common-dev-'+stamp)/'ch'
backup.mkdir(mode=0o700,parents=True);release.parent.mkdir(parents=True)
run(['cp','-a','--reflink=auto',str(work/'stage'),str(release)],stdout=subprocess.DEVNULL)
labels=i['Config']['Labels'];compose=pathlib.Path(labels['com.docker.compose.project.config_files']);shutil.copy2(compose,backup/'compose.yml')
composecmd=['docker','compose','-p',labels['com.docker.compose.project']]
if labels.get('com.docker.compose.project.environment_file'):composecmd+=['--env-file',labels['com.docker.compose.project.environment_file']]
composecmd+=['-f',str(compose)]
# Older Compose projects omit an env-file label; reuse the existing DB values
# for interpolation without printing, persisting, or changing its container.
composeenv=os.environ.copy()
for entry in inspect(db)['Config']['Env']:
 name,_,value=entry.partition('=')
 if name.startswith('MARIADB_'):composeenv[name]=value
record={'site':key,'host':host,'previous':str(previous),'release':str(release),'backup':str(backup),'imageId':image,'commits':json.loads((B/'commits.json').read_text()),'manifest':sha(B/'manifest.json'),'startedUTC':stamp,'status':'prepared'}
(backup/'release.json').write_text(json.dumps(record,indent=2))
stopped=False;switched=False;locks=[];timers=[]
def invoke(mode):
 args=['docker','run','--rm','--volumes-from',web,'--network',network,'--entrypoint','php','-e','SHARED_RELEASE=20261110','-v',str(B)+':/release:ro']
 if switched and stopped:
  assert not inspect(web)['State']['Running'];args+=['-v',str(release)+':'+root+':ro','-e','SHARED_SWITCH_STOPPED=1']
 r=subprocess.run(args+[image,'/release/operator.php',mode,host,root,key],capture_output=True)
 with open(backup/'operations.log','ab') as f:f.write(r.stdout+r.stderr)
 assert r.returncode==0,'Operator failed '+mode
 text=r.stdout.decode(errors='replace');assert ('STATE ' if mode=='state' else 'PASS '+mode) in text,'Missing acknowledgement '+mode
 for line in text.splitlines():
  if line.startswith(('PASS ','STATE ','UPDATED ','MAINTENANCE_MODULE ')):print(line,flush=True)
 return text
columns=json.loads((work/'columns.json').read_text())
def sql(q):return subprocess.check_output(['docker','exec','-i',db,'sh','-c','exec mariadb -N -B -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'],input=q.encode()).decode()
def logstate():
 result={}
 for m in i['Mounts']:
  if m['Destination'] in [root+'/error_log',root+'/error_logs']:
   for p in pathlib.Path(m['Source']).rglob('*'):
    if p.is_file() and not p.is_symlink():result[str(p)]=p.stat().st_size
 return result
try:
 for timer in cfg['timers']:
  assert out(['systemctl','is-active',timer])=='active','Timer not active '+timer
  if key=='kengalearn':continue  # Its wrapper shares a readable flock; no timer mutation needed.
  run(['systemctl','stop',timer]);timers.append(timer)
  service=timer.replace('.timer','.service')
  for attempt in range(120):
   if subprocess.run(['systemctl','is-active','--quiet',service]).returncode!=0:break
   time.sleep(1)
  else:raise RuntimeError('Worker has not finished '+service)
 paths=[site/'shared-release.lock']+[pathlib.Path(p) for p in cfg['locks']]
 if key=='kengalearn':paths.append(pathlib.Path('/run/chisimba-communications-worker.lock'))
 for p in paths:
  f=open(p,'r' if p.exists() else 'a');fcntl.flock(f,fcntl.LOCK_EX|fcntl.LOCK_NB);locks.append(f)
 state=invoke('state');assert 'STATE 0\n' in state or 'STATE unset\n' in state,'Preserve existing maintenance state'
 (backup/'logs-before.json').write_text(json.dumps(logstate()))
 run(['docker','stop',web],stdout=subprocess.DEVNULL);stopped=True;print('WRITERS_STOPPED',flush=True)
 proc=subprocess.Popen(['docker','exec',db,'sh','-c','exec mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" --single-transaction --quick --routines --triggers "$MARIADB_DATABASE"'],stdout=subprocess.PIPE)
 with gzip.open(backup/'database.sql.gz','wb') as f:shutil.copyfileobj(proc.stdout,f)
 assert proc.wait()==0;(backup/'database.sql.gz').chmod(0o600)
 with gzip.open(backup/'database.sql.gz','rb') as f:
  while f.read(1048576):pass
 paths=[root+'/config']  # Source switch leaves all mounted content untouched.
 archive='tar -czpf /backup/configuration.tar.gz -C / '+' '.join(p.lstrip('/') for p in paths)+' && chown '+str(os.getuid())+':'+str(os.getgid())+' /backup/configuration.tar.gz && chmod 600 /backup/configuration.tar.gz'
 run(['docker','run','--rm','--user','0','--network','none','--volumes-from',web+':ro','-v',str(backup)+':/backup','--entrypoint','sh',image,'-c',archive],stdout=subprocess.DEVNULL)
 run(['tar','-tzf',str(backup/'configuration.tar.gz')],stdout=subprocess.DEVNULL)
 navigation_before=navigation_fingerprints(sql);(backup/'navigation-protected-before.json').write_text(json.dumps(navigation_before))
 before=fingerprint(sql,columns);(backup/'protected-before.json').write_text(json.dumps(before,indent=2));print('BACKUPS_VERIFIED',flush=True)
 for r in baseline['files']:assert sha(previous/r['path'])==r['before'],'Live drift before switch'
 link=current.parent/('.pear-link-'+stamp);assert not link.exists();link.symlink_to(release)
 if not current.is_symlink():
  # Older sites use an ordinary code directory; retain it intact for rollback.
  retained=site/('app-before-pear-'+stamp);assert not retained.exists();os.rename(current,retained);record['previous']=str(retained)
 os.replace(link,current);switched=True
 invoke('upgrade');invoke('verify')
 navigation_after=navigation_fingerprints(sql);(backup/'navigation-protected-after.json').write_text(json.dumps(navigation_after));assert all(row in navigation_after for row in navigation_before),'Existing site navigation changed'
 after=fingerprint(sql,columns);(backup/'protected-after.json').write_text(json.dumps(after,indent=2));assert before==after,'Protected data changed '+','.join(t for t in before if before[t]!=after[t])
 for r in manifest:assert sha(release/r['path'])==r['after'],'Deployed hash mismatch '+r['path']
 run(composecmd+['up','-d','--no-deps','--no-build','--force-recreate','web'],stdout=subprocess.DEVNULL,env=composeenv);stopped=False
 for attempt in range(30):
  r=subprocess.run(['curl','-sS','-L','-o','/dev/null','-w','%{http_code}','--max-time','5','https://'+host+'/index.php?module=security&action=showlogin'],capture_output=True)
  if r.returncode==0 and r.stdout==b'200':break
  time.sleep(1)
 else:raise RuntimeError('HTTP readiness failed')
 for route in ['', 'index.php?module=security&action=showlogin','index.php?module=registration-service&action=forgotpassword']:
  r=subprocess.run(['curl','-sS','-L','--max-time','20','https://'+host+'/'+route],capture_output=True);assert r.returncode==0 and r.stdout and not re.search(rb'(Fatal error|Uncaught .*Exception|SQLSTATE\[)',r.stdout),'Public smoke failed';print('HTTP_PASS '+route,flush=True)
 verify_source(release)
 record.update(status='online-verified',database=sha(backup/'database.sql.gz'),persistentFiles=sha(backup/'configuration.tar.gz'),protectedTables=len(before))
 (backup/'release.json').write_text(json.dumps(record,indent=2));(work/'deployed.json').write_text(json.dumps(record,indent=2));print('DEPLOYED '+str(backup),flush=True)
except BaseException:
 record.update(status='stopped-for-diagnosis',stopped=stopped,switched=switched);(backup/'release.json').write_text(json.dumps(record,indent=2))
 print('STOPPED for diagnosis. Previous code, database and persistent files retained at '+str(backup)+'. No database rollback attempted.',flush=True)
 raise
finally:
 for f in locks:f.close()
 # Resume timers only when the web container is serving, including pre-switch failures.
 if not stopped:
  for timer in timers:run(['systemctl','start',timer])
