<?php
/** Guard complete ExtJS runtime retirement after native caller migration. */
$root=dirname(__DIR__,2);
foreach(json_decode(file_get_contents(__DIR__.'/retired-extjs-paths.json'),true,512,JSON_THROW_ON_ERROR) as $path){
 if(file_exists($root.'/'.$path))throw new RuntimeException('Retired ExtJS path returned: '.$path);
}
$roots=[$root.'/app'];
if(is_dir(dirname($root).'/modules'))$roots[]=dirname($root).'/modules';
$checked=0;
foreach($roots as $source){
 $iterator=new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($source,FilesystemIterator::SKIP_DOTS),function($file){return !($file->isDir() && (str_ends_with($file->getFilename(),'__PAUSED')||in_array($file->getFilename(),['.git','tests','docs'],true)));}));
 foreach($iterator as $file){
  if(!$file->isFile()||!in_array(strtolower($file->getExtension()),['php','js','css','html','conf'],true))continue;
  $text=file_get_contents($file->getPathname());$checked++;
  if(preg_match('~\bExt\s*\.|ext-all\.(?:js|css)|ext-base\.js|ext-3\.[0-9]|css/extjs/|(?:getObject|newObject|loadClass)\s*\(\s*[\x22\x27]extjs[\x22\x27]|^DEPENDS:\s*ext\s*$~m',$text))throw new RuntimeException('Active ExtJS reference: '.$file->getPathname());
 }
}
echo 'PASS: retired bundles/loaders absent; '.$checked." active source files contain no ExtJS runtime references. Paused source excluded.\n";
