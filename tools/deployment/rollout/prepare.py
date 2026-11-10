from common import *
work.mkdir(mode=0o700,exist_ok=True);stage=work/'stage';current=current.resolve();expected=json.loads((B/(key+'-hashes.json')).read_text())
verify_baseline(current)
if '--restage' in sys.argv and stage.exists():
 assert not (work/'deployed.json').exists(), 'Cannot restage a deployed operation'
 stage.rename(work/('stage-replaced-'+str(time.time_ns())))
 (work/'lint.log').unlink(missing_ok=True)
 (work/'prepared.json').unlink(missing_ok=True)
i=json.loads(out(['docker','inspect',web]))[0];image=i['Image']
baseline={'code':str(current),'image':i['Config']['Image'],'imageId':i['Image'],'files':[]}
for r in manifest:
 assert sha(current/r['path'])==expected.get(r['path']),'Live drift '+r['path']
 baseline['files'].append({'path':r['path'],'before':expected.get(r['path'])})
(work/'baseline.json').write_text(json.dumps(baseline,indent=2))
if stage.exists():
 for r in manifest:assert sha(stage/r['path'])==r['after'],'Staged drift '+r['path']
else:
 run(['cp','-a','--reflink=auto',str(current),str(stage)],stdout=subprocess.DEVNULL)
 for r in manifest:
  p=stage/r['path'];assert not p.is_symlink() and p.resolve().is_relative_to(stage.resolve())
  if r['after'] is None:p.unlink(missing_ok=True);continue
  source=B/'payload'/r['path'];assert sha(source)==r['after'];p.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(source,p);p.chmod(p.stat().st_mode|0o444)
  for d in p.parents:
   if d==stage:break
   d.chmod(d.stat().st_mode|0o555)
prune(stage)
verify_source(stage)
(work/'effective-manifest.json').write_text(json.dumps(manifest))
print(key+' STAGED',flush=True)
# Compile using the exact candidate image and candidate filesystem in one invocation.
linter=work/'lint.php';linter.write_text('''<?php $bad=[];foreach(json_decode(file_get_contents('/release/__KEY__/effective-manifest.json'),true) as $r){if($r['after']===null||($r['before']??null)===$r['after']||!preg_match('/\\.(php|inc)$/',$r['path']))continue;$extra=str_ends_with($r['path'],'ai/tests/transport_failure_test.php')?' -d disable_functions=curl_init,curl_setopt_array,curl_exec,curl_error,curl_errno,curl_getinfo':'';$cmd=escapeshellarg(PHP_BINARY).$extra.' -d error_reporting=-1 -d display_errors=1 -l '.escapeshellarg('__ROOT__/'. $r['path']);exec($cmd.' 2>&1',$o,$s);if($s||preg_grep('/Warning|Deprecated|Fatal|Parse error/',$o))$bad[$r['path']]=$o;$o=[];}echo json_encode($bad);exit(count($bad)?1:0);'''.replace('__ROOT__',root).replace('__KEY__',key))
if not (work/'lint.log').exists() or (work/'lint.log').read_text()!='[]':
 r=subprocess.run(['docker','run','--rm','--network','none','--entrypoint','php','-v',str(stage)+':'+root+':ro','-v',str(B)+':/release:ro',image,'/release/'+key+'/lint.php'],capture_output=True);(work/'lint.log').write_bytes(r.stdout+r.stderr);assert r.returncode==0,'Lint failed';print(key+' LINT_PASS',flush=True)
dump=work/'database.sql.gz';proc=subprocess.Popen(['docker','exec',db,'sh','-c','exec mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" --single-transaction --quick --routines --triggers "$MARIADB_DATABASE"'],stdout=subprocess.PIPE)
with gzip.open(dump,'wb') as f:shutil.copyfileobj(proc.stdout,f)
assert proc.wait()==0;dump.chmod(0o600)
network='common-dev-'+key;container=network+'-db';password=secrets.token_hex(24)
run(['docker','network','create','--internal',network],stdout=subprocess.DEVNULL)
try:
 run(['docker','run','-d','--rm','--name',container,'--network',network,'--network-alias','release-db','--tmpfs','/var/lib/mysql','-e','MARIADB_ROOT_PASSWORD='+password,'-e','MARIADB_DATABASE=chisimba_rehearsal',inspect(db)['Image']],stdout=subprocess.DEVNULL)
 for attempt in range(45):
  if subprocess.run(['docker','exec',container,'mariadb','-uroot','-p'+password,'chisimba_rehearsal','-e','SELECT 1'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0:break
  time.sleep(1)
 else:raise RuntimeError('Clone not ready')
 with gzip.open(dump,'rb') as f:
  p=subprocess.Popen(['docker','exec','-i',container,'mariadb','-uroot','-p'+password,'chisimba_rehearsal'],stdin=subprocess.PIPE);shutil.copyfileobj(f,p.stdin);p.stdin.close();assert p.wait()==0
 def query(sql):return subprocess.check_output(['docker','exec','-i',container,'mariadb','-N','-B','-uroot','-p'+password,'chisimba_rehearsal'],input=sql.encode()).decode()
 excluded=('tbl_module_blocks','tbl_modules','tbl_en','tbl_languagetext','tbl_menu','tbl_perms','tbl_sysconfig','tbl_systext','tbl_permissions','tbl_groupadmin','tbl_frontpage','tbl_userparams')
 columns={}
 for row in query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION;").splitlines():
  table,field=row.split('\t');
  if table.startswith(excluded):continue
  assert re.fullmatch('[a-zA-Z0-9_]+',table) and re.fullmatch('[a-zA-Z0-9_]+',field)
  columns.setdefault(table,[]).append(field)
 def fingerprint():
  statements=[];grouped={t:[] for t in columns}
  for table,fields in columns.items():
   statements.append("SELECT '"+table+"',SHA2(CAST(JSON_ARRAY("+','.join('`'+f+'`' for f in fields)+") AS CHAR),256) AS rowhash FROM `"+table+"` ORDER BY rowhash;")
  for row in query('\n'.join(statements)).splitlines():
   table,digest=row.split('\t');grouped[table].append(digest+'\n')
  return {t:{'rows':len(rows),'sha256':hashlib.sha256(''.join(rows).encode()).hexdigest()} for t,rows in grouped.items()}
 navigation_before=navigation_fingerprints(query);(work/'navigation-protected-before.json').write_text(json.dumps(navigation_before))
 before=fingerprint();(work/'columns.json').write_text(json.dumps(columns));(work/'protected-before.json').write_text(json.dumps(before,indent=2))
 clone=work/'config';clone.mkdir(mode=0o700,exist_ok=True)
 run(['docker','run','--rm','--network','none','--entrypoint','sh','-v',next(m['Source'] for m in i['Mounts'] if m['Destination']==root+'/config')+':/source:ro','-v',str(clone)+':/dest',image,'-c','cp -a /source/. /dest/ && chown -R '+str(os.getuid())+':'+str(os.getgid())+' /dest'])
 (clone/'dbdetails_inc.php').unlink();(clone/'dbdetails_inc.php').write_text("<?php define('KEWL_DB_DSN','mysqli://root:"+password+"@release-db/chisimba_rehearsal');\n");(clone/'dbdetails_inc.php').chmod(0o600)
 # Preserve the site's selected database backend in the rehearsal.
 args=['docker','run','--rm','--network',network,'--entrypoint','php','-e','SHARED_RELEASE=20261110','-e','REHEARSAL=1','-v',str(stage)+':'+root+':ro','-v',str(clone)+':'+root+'/config','-v',str(B)+':/release:ro','--tmpfs',root+'/error_log','--tmpfs',root+'/error_logs','--tmpfs','/var/lib/php/sessions','--tmpfs',root+'/usrfiles','--tmpfs',root+'/user_images','--tmpfs','/var/data/filemanager']
 for m in i['Mounts']:
  if m['Destination'].startswith('/usr/local/etc/php/'):args+=['-v',m['Source']+':'+m['Destination']+':ro']
 for label,mode in [('upgrade','upgrade'),('repeat','upgrade'),('verify','verify')]:
  r=subprocess.run(args+[image,'/release/operator.php',mode,host,root,key],capture_output=True);(work/(label+'.log')).write_bytes(r.stdout+r.stderr)
  for line in r.stdout.decode(errors='replace').splitlines():
   if line.startswith(('PASS ','UPDATED ')):print(key+' '+line,flush=True)
  assert r.returncode==0 and ('PASS '+mode).encode() in r.stdout,'Rehearsal failed '+label+'; see '+str(work/(label+'.log'))
 # Exercise the actual anonymous homepage/login routes before the live switch.
 clone.chmod(0o755)
 for config_dir in clone.rglob('*'):
  if config_dir.is_dir():config_dir.chmod(config_dir.stat().st_mode|0o555)
 for config_file in clone.rglob('*'):
  if config_file.is_file():config_file.chmod(config_file.stat().st_mode|0o444)
 http_name='shared-rehearsal-'+key+'-http'
 http_args=args.copy();http_args.remove('--rm');http_args[2:2]=['-d','--name',http_name];http_args[http_args.index('--entrypoint')+1]=i['Config']['Entrypoint'][0]
 for arg_index,arg in enumerate(http_args):
  if arg=='--tmpfs':http_args[arg_index+1]+=':mode=1777'
 http_conf=work/'http-rehearsal.conf';http_conf.write_text('SetEnv HTTPS on\nServerName '+host+'\n');http_conf.chmod(0o644)
 http_args+=['-v',str(http_conf)+':/etc/apache2/conf-enabled/zz-rehearsal.conf:ro']
 for m in i['Mounts']:
  if m['Destination'].startswith('/etc/apache2/'):
   http_args+=['-v',m['Source']+':'+m['Destination']+':ro']
 try:
  run(http_args+[image]+(i['Config']['Entrypoint'][1:])+(i['Config']['Cmd'] or []),stdout=subprocess.DEVNULL)

  for route in ['', 'index.php?module=security&action=showlogin','index.php?module=registration-service&action=forgotpassword']:
   for attempt in range(20):
    r=subprocess.run(['docker','exec',http_name,'php','/release/http-probe.php',('www.chisimba.com' if key=='chisimba' else host),route],capture_output=True)
    if r.returncode==0 and not r.stdout.endswith(b'STATUS:000'):break
    time.sleep(1)
   label='home' if not route else 'login' if 'showlogin' in route else 'reset'
   (work/('http-'+label+'.log')).write_bytes(r.stdout+r.stderr)
   assert r.returncode==0 and r.stdout.endswith(b'STATUS:200') and not re.search(rb'Fatal error|Uncaught|SQLSTATE\[|Warning:|Deprecated:',r.stdout),'Rehearsal HTTP failed '+label
   print(key+' HTTP_REHEARSAL_PASS '+label,flush=True)
 finally:
  logs=subprocess.run(['docker','logs',http_name],capture_output=True);(work/'http-container.log').write_bytes(logs.stdout+logs.stderr)
  subprocess.run(['docker','rm','-f',http_name],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
 navigation_after=navigation_fingerprints(query);(work/'navigation-protected-after.json').write_text(json.dumps(navigation_after));assert all(row in navigation_after for row in navigation_before),'Existing site navigation changed'
 after=fingerprint();(work/'protected-after.json').write_text(json.dumps(after,indent=2));assert before==after,'Business data changed '+','.join(t for t in before if before[t]!=after[t])
 (work/'prepared.json').write_text(json.dumps({'manifest':sha(B/'manifest.json'),'effectiveManifest':sha(work/'effective-manifest.json'),'imageId':json.loads(out(['docker','image','inspect',image]))[0]['Id'],'image':image,'tables':len(before)},indent=2));print(key+' PREPARED '+str(len(before))+' protected tables',flush=True)
finally:
 subprocess.run(['docker','stop',container],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL);subprocess.run(['docker','network','rm',network],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
