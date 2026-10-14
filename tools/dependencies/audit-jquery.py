#!/usr/bin/env python3
"""Read-only source inventory, not proof of runtime reachability or dead code.

Run from any directory. Pass --modules PATH for a nonstandard sibling checkout.
Output paths, line numbers and counts, never source text or configuration values.
"""
import argparse
import collections
import json
import re
from pathlib import Path

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--modules', type=Path)
args = parser.parse_args()
framework = Path(__file__).resolve().parents[2]
modules = (args.modules or framework.parent / 'modules').resolve()
patterns = {
    'jquery_name': re.compile(r'jquery', re.I),
    'jquery_api': re.compile(r'\bjQuery\s*[.(]'),
    'dollar_call_candidate': re.compile(r'(?<![\w$])\$\s*\('),
    'shared_loader': re.compile(r'(?:getObject|newObject|loadClass)\s*\([^\n;]{0,180}[\'\"](?:jquery|jquerycore)[\'\"]'),
    'legacy_plugin_loader': re.compile(r'->load\w*Plugin\s*\('),
    'ui_widget_candidate': re.compile(r'\.(?:dialog|tabs|accordion|datepicker|sortable|draggable|droppable|autocomplete|button)\s*\('),
}
rows = []
versions = []
for label, base in [('framework', framework / 'app'), ('modules', modules)]:
    if not base.is_dir():
        continue
    for path in sorted(base.rglob('*')):
        if not path.is_file() or path.suffix.lower() not in {'.php', '.js', '.html', '.htm', '.xml', '.conf', '.tpl'}:
            continue
        rel = path.relative_to(base)
        if any(p in {'.git', 'tests', 'docs', 'node_modules'} for p in rel.parts):
            continue
        text = path.read_text(errors='replace')
        found = {}
        for name, pattern in patterns.items():
            hits = list(pattern.finditer(text))
            if hits:
                found[name] = {'occurrences': len(hits), 'first_line': text.count('\n', 0, hits[0].start()) + 1}
        if not found:
            continue
        owner = '/'.join(rel.parts[:2]) if label == 'framework' else rel.parts[0]
        row = {'path': label + ('/app/' if label == 'framework' else '/') + str(rel),
               'owner': owner, 'paused': any('__PAUSED' in p for p in rel.parts),
               'resource_file': 'resources' in rel.parts, 'signals': found}
        rows.append(row)
        # Header evidence only: arbitrary filenames and plugin version strings
        # must not be mistaken for a detected jQuery core/UI version.
        for kind, pattern in [('core', r'(?m)^[ \t/*!]*jQuery (?:JavaScript Library )?v?(\d+\.\d+(?:\.\d+)?)(?=\s*(?:\r?$|[-,<]))'),
                              ('ui', r'(?m)^[ \t/*!]*jQuery UI(?: -)? v?(\d+\.\d+(?:\.\d+)?)(?=\s*(?:\r?$|[-,<]))')]:
            match = re.search(pattern, text[:8192]) if path.suffix == '.js' else None
            if match:
                versions.append({'path': row['path'], 'kind': kind, 'header_version': match[1]})
summary = collections.Counter()
owners = collections.Counter()
for row in rows:
    summary['paused_files' if row['paused'] else 'nonpaused_files'] += 1
    if not row['paused']:
        owners[row['owner']] += 1
        for name in row['signals']:
            summary[name + '_files'] += 1
print(json.dumps({'limitations': 'Text candidates include comments, vendors, Prototype dollar syntax and unregistered modules. Registration and browser tracing are separate. No deletion decisions follow from these counts alone.',
                  'summary': dict(summary), 'owners': dict(owners.most_common()),
                  'detected_headers': versions, 'files': rows}, indent=2))
