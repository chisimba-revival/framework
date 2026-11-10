#!/usr/bin/env python3
"""Read-only production source audit against a reviewed fleet manifest.

Checks host source and the bytes visible inside each running container. Also
reports newer shared source on the common GitHub development branch. It never
repairs production or changes source checkouts.
"""
import argparse
import concurrent.futures
import json
from pathlib import Path
import shlex
import subprocess
import sys


def audit_host(host, sites):
    verifier = Path(__file__).with_name('source_manifest.py').read_text()
    # Run our local verifier over SSH, rather than trusting a mutable remote tool.
    code = "__name__='fleet_verifier'\n" + verifier + '''
import subprocess
sites=json.loads(sys.argv[1]);results=[]
for site in sites:
 try:
  raw=Path(site['manifest']).read_bytes()
  if digest(raw)!=site['manifest_sha256']:raise RuntimeError('Approved manifest changed')
  manifest=json.loads(raw)
  overlay_raw=Path(site['overlays']).read_bytes()
  if digest(overlay_raw)!=site['overlays_sha256']:raise RuntimeError('Private overlay inventory changed')
  overlay=json.loads(overlay_raw).get(site['key'],{})
  errors=verify(site['source'],manifest,overlay)
  container=json.loads(subprocess.check_output(['docker','inspect',site['container']]))[0]
  if not container['State']['Running']:errors.append('web container is stopped')
  for mount in container['Mounts']:
   dest=mount['Destination']
   if dest==site['runtime_root'] and mount['RW']:errors.append('source mount is writable')
   if dest.startswith(site['runtime_root']+'/'):
    relative=dest[len(site['runtime_root'])+1:]
    if source_path(relative):errors.append('unexpected source mount: '+relative)
  expected=dict(manifest['files']);expected.update(overlay)
  php='$expected=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);$errors=[];foreach($expected as $p=>$h){$f=$argv[1]."/".$p;if(!is_file($f)||hash_file("sha256",$f)!==$h)$errors[]="container source differs: ".$p;}echo json_encode($errors);'
  visible=json.loads(subprocess.check_output(['docker','exec','-i',site['container'],'php','-r',php,site['runtime_root']],input=json.dumps(expected).encode()))
  errors.extend(visible)
  results.append({'site':site['site'],'ok':not errors,'issues':errors,'pins':manifest['pins'],'files':len(expected)})
 except Exception as error:results.append({'site':site['site'],'ok':False,'issues':[str(error)]})
print(json.dumps(results))
'''
    raw = subprocess.check_output(['ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15', host,
                                   'python3 - ' + shlex.quote(json.dumps(sites))], input=code.encode(), timeout=180)
    return json.loads(raw)


def newer_source(repo, pinned, branch, paths):
    remote = subprocess.check_output(['git', '-C', repo, 'ls-remote', '--exit-code', 'origin', 'refs/heads/' + branch]).decode().split()[0]
    if remote == pinned:
        return []
    subprocess.run(['git', '-C', repo, 'fetch', '--quiet', '--no-tags', 'origin', remote], check=True)
    return subprocess.check_output(['git', '-C', repo, 'diff', '--name-only', pinned, remote, '--', *paths]).decode().splitlines()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--fleet', default=str(Path(__file__).with_name('fleet.json')))
    parser.add_argument('--framework', required=True)
    parser.add_argument('--modules', required=True)
    args = parser.parse_args()
    fleet = json.loads(Path(args.fleet).read_text())
    groups = {}
    for site in fleet['sites']:
        groups.setdefault(site['ssh'], []).append(site)
    results = []
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        futures = {pool.submit(audit_host, host, sites): host for host, sites in groups.items()}
        for future, host in futures.items():
            try:
                results.extend(future.result())
            except Exception as error:
                results.extend({'site':s['site'],'ok':False,'issues':[str(error)]} for s in groups[host])
    newer = {}
    try:
        for name, repo, paths in [('framework', args.framework, ['app', 'tools/deployment/module-profile.json']),
                                   ('modules', args.modules, fleet['module_profile'])]:
            pin = fleet['pins'][name]
            newer[name] = newer_source(repo, pin['commit'], pin['branch'], paths)
    except Exception as error:
        newer['check_error'] = [str(error)]
    report = {'ok':all(r['ok'] for r in results) and not any(newer.values()), 'sites':results, 'newer_shared_source':newer}
    print(json.dumps(report, indent=2))
    return 0 if report['ok'] else 1


if __name__ == '__main__':
    sys.exit(main())
