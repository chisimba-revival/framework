<?php
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/textpostinput_class_inc.php';
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); echo "PASS $label\n"; }
$input = TextPostInput::normalise(['title'=>' Test ', 'content'=>"First\r\n<img src=x onerror=alert(1)>"], 'application/json; charset=utf-8', false);
check($input['status']==='draft' && $input['title']==='Test', 'default draft and trimmed title');
check(strpos($input['content'], '<img')===false && strpos($input['content'],'&lt;img')!==false && strpos($input['content'],'<br>')!==false, 'plain text escapes markup and preserves lines');
foreach ([['title'=>'t','content'=>''], ['title'=>'t','content'=>'x','userid'=>'other'], ['title'=>'t','content'=>'x','featured_image'=>'https://example.invalid/x.png'], ['title'=>'t','content'=>'x','status'=>'public'], ['title'=>[], 'content'=>'x'], ['title'=>'t','content'=>str_repeat('x',50001)], ['title'=>str_repeat('x',251),'content'=>'x']] as $bad) {
 try { TextPostInput::normalise($bad,'application/json',false); throw new RuntimeException('Invalid input accepted'); }
 catch (DomainException $e) { echo "PASS invalid input rejected\n"; }
}
foreach ([['multipart/form-data',false], ['application/json',true]] as [$type,$files]) {
 try { TextPostInput::normalise(['title'=>'t','content'=>'x'],$type,$files); throw new RuntimeException('Media request accepted'); }
 catch (DomainException $e) { echo "PASS media request rejected\n"; }
}
$categorised=TextPostInput::normalise(['title'=>'t','content'=>'x','categories'=>['abc','abc','def']],'application/json',false);
check($categorised['categories']===['abc','def'],'category IDs survive text translation without duplicates');
foreach ([null,'abc',[2],[''],['bad/id'],array_fill(0,101,'abc'),['key'=>'abc']] as $ids) {
 try {TextPostInput::normalise(['title'=>'t','content'=>'x','categories'=>$ids],'application/json',false);throw new RuntimeException('Invalid categories accepted');}
 catch(DomainException $e){echo "PASS invalid categories rejected\n";}
}
