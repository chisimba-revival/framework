import subprocess,tarfile,io,json,shutil,secrets,base64
from pathlib import Path
import xml.etree.ElementTree as ET
import argparse
parser=argparse.ArgumentParser(description='Assemble an isolated local preview from committed Git objects')
parser.add_argument('workspace',type=Path)
parser.add_argument('--config-template',type=Path,required=True,help='Existing local configuration directory (never production)')
args=parser.parse_args()
w=args.workspace.resolve()
template=args.config_template.resolve()
r=w/'dev-environment/runtime/ltb-design-preview';app=r/'app';app.mkdir(parents=True,exist_ok=True)
if (app/'index.php').exists():raise SystemExit('Refusing to overwrite an existing preview')
data=subprocess.check_output(['git','-C',str(w/'framework'),'archive','HEAD','app'])
with tarfile.open(fileobj=io.BytesIO(data)) as t:
 for m in t.getmembers():
  m.name=m.name.removeprefix('app/')
  if m.name=='app':continue
  t.extract(m,app,filter='data')
profile=json.loads((w/'framework/tools/deployment/fleet.json').read_text())['module_profile']
data=subprocess.check_output(['git','-C',str(w/'modules'),'archive','HEAD',*profile])
(app/'packages').mkdir(exist_ok=True)
with tarfile.open(fileobj=io.BytesIO(data)) as t:t.extractall(app/'packages',filter='data')
(app/'config').mkdir(exist_ok=True)
xml=ET.parse(template/'config.xml');root=xml.getroot()
for k,v in {'KEWL_SITE_ROOT':'https://chisimba.test:8446/ch/','KEWL_SITEROOT':'https://chisimba.test:8446/ch/','KEWL_SITENAME':'Learn-the-Birds · local preview','KEWL_INSTITUTION_SHORTNAME':'Learn-the-Birds','KEWL_INSTITUTION_NAME':'Learn-the-Birds','KEWL_SITEEMAIL':'preview@example.invalid','KEWL_TECH_EMAIL':'preview@example.invalid','KEWL_ALLOW_SELFREGISTER':'FALSE','KEWL_POSTLOGIN_MODULE':'simpleblog'}.items():
 el=root.find(k)
 if el is None:el=ET.SubElement(root,k)
 el.text=v
xml.write(app/'config/config.xml',encoding='UTF-8',xml_declaration=True)
(app/'config/dbdetails_inc.php').write_text("<?php define('KEWL_DB_DSN','mysqli://root:preview-root-local@db/chisimba_ltb_preview');\n")
for n in ['systemtypes.xml','installdone.txt']:
 shutil.copy2(template/n,app/'config'/n)
(app/'config/installation.key').write_text(base64.b64encode(secrets.token_bytes(32)).decode()+"\n")
for d in ['usrfiles','error_log','error_logs','user_images']:(app/d).mkdir(exist_ok=True)
(r/'filemanager').mkdir(exist_ok=True)
(r/'source.json').write_text(json.dumps({repo:subprocess.check_output(['git','-C',str(w/repo),'rev-parse','HEAD'],text=True).strip() for repo in ['framework','modules']},indent=2))
print(r)
