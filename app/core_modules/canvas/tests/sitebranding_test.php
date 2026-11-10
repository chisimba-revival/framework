<?php
/** Disposable filesystem checks for the shared public branding projection. */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject { public array $services=[]; public function getObject($name,$module){return $this->services[$name];} }
require dirname(__DIR__).'/classes/sitebranding_class_inc.php';
function check($ok,$message){if (!$ok) throw new RuntimeException($message);}
$root=sys_get_temp_dir().'/branding-'.bin2hex(random_bytes(6));mkdir($root.'/skins/test/canvases/brand',0700,true);
$config=new class($root) {
 public function __construct(public string $root){} public string $skin='test';
 public function getdefaultSkin(){return $this->skin;} public function getSiteName(){return '<b>Test site</b>';}
 public function getsiteRootPath(){return $this->root;}
};
$settings=new class {public string $canvas='brand';public function getValue($a,$b){return $this->canvas;}};
$service=new sitebranding();$service->services=['altconfig'=>$config,'dbsysconfig'=>$settings];
$dir=$root.'/skins/test/canvases/brand';$metadata=$dir.'/canvas.json';
$png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a7V0AAAAASUVORK5CYII=');
file_put_contents($root.'/skins/test/logo.png',$png);
$data=['brand'=>['primary'=>'#AaBbCc','accent'=>'red; background:url(x)'],'preview'=>['logo'=>'../../logo.png']];
try {
 file_put_contents($metadata,json_encode($data));$result=$service->getBranding();
 check($result['siteName']==='Test site' && $result['colours']['primary']==='#aabbcc' && $result['colours']['accent']===null,'Safe name and colours');
 check($result['logo']['mime']==='image/png' && base64_decode($result['logo']['base64'])===$png,'Declared raster logo');
 file_put_contents($root.'/secret.png',$png);
 foreach (['../../../../secret.png','https://example.invalid/logo.png'] as $path) {
  $data['preview']['logo']=$path;file_put_contents($metadata,json_encode($data));check($service->getBranding()['logo']===null,'Reject outside asset');
 }
 symlink($root.'/secret.png',$dir.'/linked.png');$data['preview']['logo']='linked.png';file_put_contents($metadata,json_encode($data));check($service->getBranding()['logo']===null,'Reject escaping symlink');
 file_put_contents($dir.'/unsafe.svg','<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
 $data['preview']['logo']='unsafe.svg';file_put_contents($metadata,json_encode($data));check($service->getBranding()['logo']===null,'Reject SVG');
 file_put_contents($metadata,'{broken');check($service->getBranding()['logo']===null,'Malformed metadata fallback');
 $settings->canvas='../brand';check($service->getBranding()['canvas']===null,'Invalid canvas selection');
 $config->skin='../test';check($service->getBranding()['logo']===null,'Invalid skin selection');
 echo "PASS public branding, colours, raster image, path/symlink confinement and fallbacks\n";
} finally {
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){if($f->isDir()&&!$f->isLink())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
