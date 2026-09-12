<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public function getResourceUri($file,$module){return '/resources/'.$file;} }
require dirname(__DIR__).'/classes/editoradapter_class_inc.php';
$r=(new editoradapter())->render(['name'=>'blocks[0][text]','id'=>'block_a','value'=>'<p>Keep this</p>','siteRoot'=>'/index.php','tinymceUri'=>'/tinymce.js']);
if(str_contains($r['html'],'<script'))throw new RuntimeException('Fragments must not execute inline scripts');
preg_match('/data-chisimba-editor="([^"]+)"/',$r['html'],$m);
$config=json_decode(html_entity_decode($m[1],ENT_QUOTES),true,512,JSON_THROW_ON_ERROR);
if($config['selector']!=='#block_a' || !str_contains($config['toolbar'],'forecolor backcolor'))throw new RuntimeException('Lost native field configuration');
if(!str_contains($r['html'],'&lt;p&gt;Keep this&lt;/p&gt;') || !str_contains(implode('',$r['headers']),'editoradapter.js'))throw new RuntimeException('Lost content or lifecycle assets');
echo "PASS: declarative rich-text fragments preserve native configuration and escaped content\n";
