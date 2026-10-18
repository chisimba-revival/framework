"""Check actual template event attributes across HTML and JavaScript boundaries."""
from pathlib import Path
from html.parser import HTMLParser
import re, subprocess
source = (Path(__file__).parents[1] / 'templates/content/native_showfolder_tpl.php').read_text()
attributes = re.findall(r'(?:onerror|onsubmit)=".*?\?>.*?"', source)
assert len(attributes) == 3
class Events(HTMLParser):
    def handle_starttag(self, tag, attrs):
        for key, value in attrs:
            if key.startswith('on'):
                subprocess.run(['node', '-e', 'new Function(process.argv[1])', value], check=True)
                assert 'fixture' in value
for text in ['fixture', 'fixture "double" and \'single\' & <tag>', 'fixture </script>\\n雪']:
    # Data is passed on stdin, never interpolated as PHP source.
    php = '''<?php $value=stream_get_contents(STDIN); $fmText=fn($key)=>$value;
$escape=fn($value)=>htmlspecialchars($value,ENT_QUOTES,'UTF-8'); ?>'''
    import tempfile
    with tempfile.NamedTemporaryFile(suffix='.php', mode='w') as fixture:
        fixture.write(php + ''.join('<img '+a+'>' for a in attributes)); fixture.flush()
        rendered = subprocess.check_output(['php', fixture.name], input=text.encode()).decode()
    Events().feed(rendered)
assert 'parentNode.textContent=' in attributes[1]
print('PASS thumbnail fallback and both deletion confirmations: quotes, HTML and Unicode')
