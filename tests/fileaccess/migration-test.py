import tempfile,pathlib,subprocess,json,os
script=pathlib.Path(__file__).resolve().parents[2]/'tools/file-storage/migrate-course.py'
with tempfile.TemporaryDirectory() as tmp:
 root=pathlib.Path(tmp);pub=root/'public';sec=root/'secure';pub.mkdir();sec.mkdir();src=pub/'context/course';src.mkdir(parents=True);(src/'file.txt').write_text('original');os.chmod(src,0o775)
 args=['python3',str(script),'--public-root',str(pub),'--secure-root',str(sec),'--course','course']
 plan=json.loads(subprocess.check_output(args));assert plan['state']=='planned' and not (sec/'context').exists()
 refused=subprocess.run(args+['--apply'],capture_output=True);assert refused.returncode and not src.is_symlink()
 dest=sec/'context/course';dest.mkdir(parents=True);(dest/'file.txt').write_text('conflict')
 conflict=subprocess.run(args+['--apply','--writes-paused','--routing-verified'],capture_output=True);assert conflict.returncode and (src/'file.txt').read_text()=='original'
 (dest/'file.txt').unlink();dest.rmdir()
 result=json.loads(subprocess.check_output(args+['--apply','--writes-paused','--routing-verified']));assert result['state']=='migrated'
 assert src.is_symlink() and (src/'file.txt').read_text()=='original' and (dest/'file.txt').read_text()=='original'
 assert (dest.stat().st_mode & 0o777)==0o775
 assert (pathlib.Path(result['journal'])/'original/file.txt').read_text()=='original'
 again=json.loads(subprocess.check_output(args+['--apply','--writes-paused','--routing-verified']));assert again['state']=='already_migrated'
 (src/'new.txt').write_text('new upload');assert (dest/'new.txt').read_text()=='new upload'
 (src/'file.txt').unlink();assert not (dest/'file.txt').exists()
 finaliser=script.with_name('finalise-context-root.py')
 finalargs=['python3',str(finaliser),'--public-root',str(pub),'--secure-root',str(sec)]
 assert subprocess.run(finalargs,capture_output=True).returncode==0
 subprocess.run(finalargs+['--apply','--writes-paused','--routing-verified'],check=True,capture_output=True)
 assert (pub/'context').is_symlink()
 new=pub/'context/new-course';new.mkdir();(new/'new.txt').write_text('future upload')
 assert (sec/'context/new-course/new.txt').read_text()=='future upload'
 assert json.loads(subprocess.check_output(args))['state']=='already_migrated'
print('Migration dry-run, prerequisites, conflict preservation, hashes, permissions, recovery, repeat run, legacy write/delete: passed')
