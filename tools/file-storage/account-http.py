import json,re,requests,urllib3,time,pathlib,subprocess
urllib3.disable_warnings();base='https://chisimba.test:8445/ch/';url=base+'index.php';f=json.load(open('/tmp/course-accounts.json'));sessions={};results=[]
for role,u in f['users'].items():
 s=requests.Session();s.verify=False;r=s.get(url,params={'module':'security','action':'showlogin'});hidden=dict(re.findall(r'<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"',r.text));hidden.update(action='login',username=u['id'],password=u['password'],website='');time.sleep(3);r=s.post(url,params={'module':'security'},data=hidden);assert 'name="native_auth_logout"' in r.text,'login failed '+role;sessions[role]=s;print('Authenticated',role,flush=True)
s=requests.Session();s.verify=False;sessions['anonymous']=s
pathlib.Path('/tmp/course-sessions.json').write_text(json.dumps({r:s.cookies.get_dict() for r,s in sessions.items()}))
def sql(q):
 return subprocess.check_output(['docker','exec','chisimba-php85-db','mariadb','-N','-uroot','-proot','chisimba','-e',q],text=True)
def test(phase):
 for role,s in sessions.items():
  s.get(url,params={'module':'context','action':'joincontext','contextcode':f['course']})
  for kind,item in f['files'].items():
   routes={'id':(url,{'module':'filemanager','action':'file','id':item['id'],'filename':'fixture.txt'}),'secure_path':(url,{'module':'filemanager','action':'downloadsecurefile','path':item['path'],'filename':'fixture.txt'}),'static':(base+f['publicUrl']+item['path'],{}),'preview':(url,{'module':'filemanager','action':'fileinfo','id':item['id']})}
   if kind=='submission':routes['assignment']=(url,{'module':'assignment','action':'downloadfile','id':f['course'],'fileid':item['id']})
   for route,(target,params) in routes.items():
    r=s.get(target,params=params,timeout=30);has=item['body'].encode() in r.content
    member=role in ['submitter','teacher','admin'] or (role=='peer' and phase!='revoked')
    expected=(phase=='public' or member) if kind=='public' else member
    if kind=='submission':expected=route=='assignment' and role in ['submitter','teacher','admin']
    if route=='secure_path' and kind=='public':expected=False
    if route=='static' and kind=='submission':expected=False
    if route=='preview':expected=False # preview must not expose the plain text fixture (separate UI outcome recorded)
    results.append(dict(phase=phase,role=role,kind=kind,route=route,status=r.status_code,bytes=has,expected=expected,pass_=has==expected))
    if has!=expected:print('MISMATCH',phase,role,kind,route,r.status_code,has,expected,flush=True)
    if route=='assignment' and expected and not has:pathlib.Path('/tmp/course-assignment-'+role+'.html').write_text(r.text)
  print('Checked',phase,role,flush=True)
for phase in ['private','public','revoked']:
 sql("UPDATE tbl_context SET access='"+('Public' if phase=='public' else 'Private')+"' WHERE contextcode='"+f['course']+"';")
 if phase=='revoked':
  sql("DELETE gu FROM tbl_perms_groupusers gu JOIN tbl_perms_perm_users pu ON pu.perm_user_id=gu.perm_user_id JOIN tbl_perms_groups g ON g.group_id=gu.group_id WHERE pu.auth_user_id='"+f['users']['peer']['id']+"' AND g.group_define_name LIKE '"+f['course']+"%';")
 test(phase)
 pathlib.Path('work/php-warning-audit/course-access/delivery-account-results.json').write_text(json.dumps(results,indent=2))
print('RESULT',len(results),'checks',sum(not x['pass_'] for x in results),'mismatches')
