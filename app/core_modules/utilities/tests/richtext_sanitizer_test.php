<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/richtextsanitizer_class_inc.php';
$s=new richtextsanitizer();
foreach(array('<script>alert(1)</script><p>Safe</p>','<img src=x onerror=alert(1)>','<a href="jav&#x61;script:alert(1)">link</a>','<svg><script>alert(1)</script></svg>','<math><mtext><img src=x onerror=alert(1)></mtext></math>','<p style="background:url(javascript:bad())">safe</p>','<iframe src="https://example.com"></iframe>') as $input) {
 $clean=$s->cleanHtml($input);
 if(preg_match('/<script|onerror|javascript:|<svg|<math|<iframe|style=/i',$clean))throw new RuntimeException($clean);
 if($s->cleanHtml($clean)!==$clean)throw new RuntimeException('Sanitisation must be idempotent');
}
$clean=$s->cleanHtml('<h2>A title</h2><p>Birds &amp; grasses <strong>matter</strong>.</p><img src="/files/bird.jpg" alt="Bird in a tree">');
if(!str_contains($clean,'alt="Bird in a tree"')||!str_contains($clean,'<strong>matter</strong>'))throw new RuntimeException('Rich content lost');
echo "PASS: active markup removed, rich text/images preserved, output idempotent\n";
