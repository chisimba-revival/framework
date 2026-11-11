<?php
/** Fixed routes must not accidentally accept subresources or API keys. */
$GLOBALS['kewl_entry_point_run']=true;
class controller {}
require __DIR__.'/../classes/restcontroller_class_inc.php';
require __DIR__.'/../classes/sightssoundsendpoints_trait.php';
class PracticeRouteTest extends RestController {
    use SightsSoundsEndpoints;
    public function __construct() {}
    public function route($path,$method){$this->routeParams=$this->parseRoute($path);return $this->resolveHandler($method);}
    public function scope($path,$method){$p=$this->parseRoute($path);return $this->scopeForRoute($p['resource'],$p['id'],$p['sub'],$method);}
}
$test=new PracticeRouteTest();
foreach(['catalogue'=>'GET','package'=>'POST','media'=>'GET','submit'=>'POST'] as $action=>$method){
    if($test->route('sightsandsounds/'.$action,$method)!==strtolower($method).'_sightsandsounds_'.$action)throw new RuntimeException('Missing route');
    if($test->route('sightsandsounds/'.$action.'/foreign',$method)!==null)throw new RuntimeException('Subresource accepted');
    if($test->scope('sightsandsounds/'.$action,$method)!=='')throw new RuntimeException('API key scope widened');
}
if($test->route('sightsandsounds/submit','GET')!==null||$test->route('sightsandsounds/private','GET')!==null)throw new RuntimeException('Invalid route accepted');
echo "PASS fixed practice routes, methods, subresource rejection and API-key exclusion\n";
