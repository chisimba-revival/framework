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
$visible=fn()=>array_values(array_filter($local,fn($id)=>$filter->includes('uninstalled',in_array($id,$installed,true))));
check($visible()===array('newtool'),'Not installed contains only uninstalled local modules');
$installed[]='newtool';
check(array_values(array_filter($local,fn($id)=>$filter->includes('uninstalled',in_array($id,$installed,true))))===array(),'installation removes the module from Not installed');
check($filter->localIds(array('newtool','oldtool','newtool'))===array('newtool','oldtool'),'numeric local lists normalise and deduplicate');
check($filter->includes('all',true)&&$filter->includes('all',false)&&!$filter->includes('installed',false),'other views retain their meaning');

$today = new DateTimeImmutable('2026-09-09');
check($filter->includes('new', true, '2026-09-01', $today), 'recent installed modules are new');
check($filter->includes('new', false, '2026-08-11', $today), '29 days old is new');
check(!$filter->includes('new', false, '2026-08-10', $today), '30 days old is excluded');
check(!$filter->includes('new', false, '2026-09-10', $today), 'future dates excluded');
check(!$filter->includes('new', false, null, $today), 'unknown dates excluded');
check(!$filter->includes('new', false, '2026-02-30', $today), 'invalid dates excluded');
check(!$filter->includes('new', false, '2010-02-08', $today), 'legacy modules stay old after updates');
check($filter->resolve('uninstalled', null, 'new') === 'uninstalled', 'Not installed is independently selectable');
