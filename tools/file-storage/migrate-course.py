#!/usr/bin/env python3
"""Copy/verify a course tree, then preserve legacy filesystem access with a link.
Dry-run by default. Requires guarded server routing and paused writes for apply.
No database changes; run with the application filesystem's permissions.
"""
import argparse, hashlib, json, os, pathlib, re, shutil, time
p=argparse.ArgumentParser();p.add_argument('--public-root',required=True);p.add_argument('--secure-root',required=True);p.add_argument('--course');p.add_argument('--derivative-directory',choices=['filemanager_thumbnails','filemanager_forcemax']);p.add_argument('--link-root',help='Secure root as seen by the application, for container mount mappings');p.add_argument('--apply',action='store_true');p.add_argument('--writes-paused',action='store_true');p.add_argument('--routing-verified',action='store_true');a=p.parse_args()
if bool(a.course)==bool(a.derivative_directory):p.error('Choose one course or derivative directory')
if a.course and not re.fullmatch(r'[A-Za-z0-9_-]+',a.course):p.error('Invalid course code')
relative=pathlib.Path('context')/a.course if a.course else pathlib.Path(a.derivative_directory)
a.course=a.course or a.derivative_directory
pub=pathlib.Path(a.public_root).resolve(strict=True);secure=pathlib.Path(a.secure_root).resolve(strict=True)
if secure==pub or pub in secure.parents or secure in pub.parents:p.error('Storage roots must be separate')
source=pub/relative;dest=secure/relative
linkdest=(pathlib.Path(a.link_root)/relative) if a.link_root else dest
if not linkdest.is_absolute():p.error('Link root must be absolute')
if relative.parts[0]=='context' and (pub/'context').is_symlink():
 expected=str(pathlib.Path(a.link_root or str(secure))/'context')
 if os.readlink(pub/'context')!=expected:p.error('Unexpected context root link')
 if dest.is_dir():print(json.dumps({'course':a.course,'state':'already_migrated'}));raise SystemExit
 p.error('Missing migrated course')
if source.is_symlink():
 if os.readlink(source)==str(linkdest) and dest.is_dir():print(json.dumps({'course':a.course,'state':'already_migrated'}));raise SystemExit
 p.error('Unexpected source symlink')
if not source.is_dir() or source.resolve().parent != (pub/relative.parent).resolve():p.error('Source course directory missing or invalid')
if dest.is_symlink():p.error('Unexpected destination symlink')
def snapshot(root):
 result={}
 for path in sorted(root.rglob('*')):
  if path.is_symlink():raise RuntimeError('Symlinks inside course tree require review')
  if path.is_file():
   digest=hashlib.sha256()
   with path.open('rb') as f:
    for chunk in iter(lambda:f.read(1048576),b''):digest.update(chunk)
   h=digest.hexdigest()
   result[str(path.relative_to(root))]={'size':path.stat().st_size,'sha256':h}
  elif not path.is_dir():raise RuntimeError('Special files require review')
 return result
files=snapshot(source);existing=snapshot(dest) if dest.exists() else {}
for name,info in files.items():
 if name in existing and existing[name]!=info:raise RuntimeError('Conflicting secure original: '+name)
manifest={'course':a.course,'source':str(source),'destination':str(dest),'files':files,'state':'planned'}
if not a.apply:print(json.dumps(manifest,indent=2));raise SystemExit
if not a.writes_paused or not a.routing_verified:p.error('Apply requires paused writes and verified server routing')
journal=secure/'.course-migrations'/(a.course+'-'+str(time.time_ns()));journal.mkdir(parents=True,mode=0o700)
(journal/'manifest.json').write_text(json.dumps(manifest,indent=2))
# Preserve write permissions/ownership for legacy upload and delete consumers.
for directory in [source]+sorted(x for x in source.rglob('*') if x.is_dir()):
 target=dest/directory.relative_to(source)
 if not target.exists():
  target.mkdir(parents=True,exist_ok=True)
  stat=directory.stat();os.chown(target,stat.st_uid,stat.st_gid);shutil.copystat(directory,target)
for name,info in files.items():
 target=dest/name;target.parent.mkdir(parents=True,exist_ok=True)
 if name not in existing:
  # Exclusive creation: never overwrite another file.
  with (source/name).open('rb') as src,target.open('xb') as dst:shutil.copyfileobj(src,dst);dst.flush();os.fsync(dst.fileno())
  stat=(source/name).stat();os.chown(target,stat.st_uid,stat.st_gid);shutil.copystat(source/name,target)
verified=snapshot(dest)
if any(verified.get(name)!=info for name,info in files.items()) or snapshot(source)!=files:raise RuntimeError('Verification failed; source remains in place')
parked=source.with_name('.migration-'+journal.name)
source.rename(parked)
try:source.symlink_to(linkdest,target_is_directory=True)
except BaseException:
 parked.rename(source);raise
# The parked name is still beneath the guarded course URL prefix until moved.
shutil.move(str(parked),str(journal/'original'))
manifest['state']='migrated';manifest['recovery_copy']=str(journal/'original')
(journal/'manifest.json').write_text(json.dumps(manifest,indent=2));print(json.dumps({'course':a.course,'state':'migrated','files':len(files),'journal':str(journal)}))
