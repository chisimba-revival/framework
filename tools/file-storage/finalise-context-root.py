#!/usr/bin/env python3
"""Finalise a fully migrated context tree. All child courses must be verified links."""
import argparse,pathlib,os,shutil,json,time
p=argparse.ArgumentParser();p.add_argument('--public-root',required=True);p.add_argument('--secure-root',required=True);p.add_argument('--link-root');p.add_argument('--apply',action='store_true');p.add_argument('--writes-paused',action='store_true');p.add_argument('--routing-verified',action='store_true');a=p.parse_args()
pub=pathlib.Path(a.public_root).resolve(strict=True);sec=pathlib.Path(a.secure_root).resolve(strict=True);src=pub/'context';dst=sec/'context';link=pathlib.Path(a.link_root or str(sec))/'context'
if sec==pub or pub in sec.parents or sec in pub.parents:p.error('Separate storage roots required')
if src.is_symlink():
 if os.readlink(src)==str(link) and dst.is_dir():print('Already finalised');raise SystemExit
 p.error('Unexpected context root link')
if not src.is_dir() or not dst.is_dir():p.error('Context roots must exist')
for child in src.iterdir():
 if not child.is_symlink() or os.readlink(child)!=str(link/child.name) or not (dst/child.name).is_dir():p.error('Course still requires migration: '+child.name)
if not a.apply:print('Ready to finalise');raise SystemExit
if not a.writes_paused or not a.routing_verified:p.error('Paused writes and verified routing required')
stat=src.stat();os.chown(dst,stat.st_uid,stat.st_gid);shutil.copystat(src,dst)
journal=sec/'.course-migrations'/('context-root-'+str(time.time_ns()));journal.mkdir(parents=True,mode=0o700)
parked=pub/('.migration-'+journal.name);src.rename(parked)
try:src.symlink_to(link,target_is_directory=True)
except BaseException:parked.rename(src);raise
shutil.move(str(parked),str(journal/'original-links'))
(journal/'manifest.json').write_text(json.dumps({'source':str(src),'destination':str(dst),'state':'finalised','link':str(link)},indent=2));print('Context root finalised')
