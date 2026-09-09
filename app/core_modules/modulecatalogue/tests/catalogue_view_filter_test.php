<?php
/** Behaviour checks for new-module discovery and session/legacy filter transitions.
 * @author Derek Keats
 * @package modulecatalogue
 */
class ChisimbaObject {}
require dirname(__DIR__).'/classes/catalogueviewfilter_class_inc.php';
$filter=new catalogueviewfilter();
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label.PHP_EOL;}
check($filter->resolve('new',null,'installed')==='new','New replaces installed-only mode');
check($filter->resolve(null,null,'new')==='new','New survives refresh and action round trips');
check($filter->resolve('all',null,'new')==='all','All clears New');
check($filter->resolve(null,'1','new')==='installed','legacy installed-only links still work');
check($filter->resolve(null,'0','new')==='all','legacy show-all links still work');
check($filter->resolve(null,null,null,true)==='installed','existing saved installed filter migrates');
check($filter->resolve(array(),null,'new')==='all','invalid filter safely resets');
$local=$filter->localIds(array('newtool'=>'New tool','oldtool'=>'Old tool'));
$installed=array('oldtool');
$visible=fn()=>array_values(array_filter($local,fn($id)=>$filter->includes('new',in_array($id,$installed,true))));
check($visible()===array('newtool'),'New contains only uninstalled local modules');
$installed[]='newtool';
check(array_values(array_filter($local,fn($id)=>$filter->includes('new',in_array($id,$installed,true))))===array(),'installation removes the module from New');
check($filter->localIds(array('newtool','oldtool','newtool'))===array('newtool','oldtool'),'numeric local lists normalise and deduplicate');
check($filter->includes('all',true)&&$filter->includes('all',false)&&!$filter->includes('installed',false),'other views retain their meaning');
