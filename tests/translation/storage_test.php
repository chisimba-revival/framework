<?php
/** Disposable database ONLY. Creates fixtures, never connects to an installed site. */
if (PHP_SAPI!=='cli'||getenv('TRANSLATION_FIXTURE')!=='1')exit(64);
$app=$argv[1];set_include_path($app.PATH_SEPARATOR.$app.'/lib/pear'.PATH_SEPARATOR.get_include_path());
require_once $app.'/classes/core/chisimbadatabaseconnection.php';
$dsn=['phptype'=>'mysqli','username'=>'root','password'=>getenv('TRANSLATION_FIXTURE_PASSWORD'),'hostspec'=>'translation-db','database'=>'translation_fixture','charset'=>'utf8mb4'];
$driver=getenv('TRANSLATION_DRIVER')?:'MDB2';
if ($driver==='PDO') {
    $db=ChisimbaDatabaseConnection::fromLegacyDsn($dsn);
} else {
    require_once 'MDB2.php';
    $db=MDB2::connect($dsn);
    if(PEAR::isError($db))throw new RuntimeException('Fixture database unavailable');
    $db->setCharset('utf8mb4');
    $db->setOption('portability', MDB2_PORTABILITY_ALL);
}
function sql($query){
    global $db;
    $r=$db instanceof ChisimbaDatabaseConnection || preg_match('/^SELECT/i',$query)
        ? $db->query($query) : $db->exec($query);
    if($db instanceof ChisimbaDatabaseConnection)return $r;
    if(PEAR::isError($r))throw new RuntimeException('Fixture query failed');
    if(is_object($r)){try{return $r->fetchAll(MDB2_FETCHMODE_ASSOC);}finally{$r->free();}}
    return $r;
}
function checkTranslation($value,$message){if(!$value)throw new RuntimeException($message);}
if (($argv[2]??'')!=='worker') {
sql('CREATE TABLE tbl_langs_avail (id varchar(32) PRIMARY KEY,name varchar(100) NOT NULL,meta varchar(100) NOT NULL,error_text varchar(100) NOT NULL,encoding varchar(255)) ENGINE=InnoDB');
sql('CREATE TABLE tbl_en (id varchar(255),pageID varchar(150),en longtext, INDEX(id)) ENGINE=InnoDB');
}
class ChisimbaObject {}
$GLOBALS['kewl_entry_point_run']=true;
require $app.'/classes/core/dbtable_class_inc.php';
require $app.'/classes/core/chisimbadatabaseschema.php';
require $app.'/core_modules/language/classes/translationstore_class_inc.php';require $app.'/core_modules/language/classes/nativetranslation.php';
// Only engine/config composition is bypassed; the real dbTable methods run.
$store=new translationstore();
(new ReflectionProperty(dbTable::class,'_db'))->setValue($store,$db);
$store->dbType='mysqli';$store->dbLayer=$driver;
(new ReflectionProperty(translationstore::class,'schema'))->setValue($store,new ChisimbaDatabaseSchema($store));
if (($argv[2]??'')==='worker') {$store->save('concurrent','fixture',['en'=>'worker']);echo 'worker-ok';exit;}
$t=new ChisimbaTranslation($store);$t->setPageID('fixture');
checkTranslation($store->languages()===[],'Empty registry remains valid');
$store->save('greeting','fixture',['en'=>'Hello 中文']);checkTranslation($t->get('greeting')==='Hello 中文','UTF-8 read/write');
$store->addLang(['lang_id'=>'fr','table_name'=>'tbl_fr','name'=>'French']);
$store->save('greeting','fixture',['fr'=>'Bonjour']);checkTranslation($t->get('greeting','fixture','fr')==='Bonjour','New language translation');
$store->save('greeting','fixture',['en'=>'Changed']);checkTranslation($t->get('greeting')==='Changed','Cache refreshed');checkTranslation($t->get('greeting','fixture','fr')==='Bonjour','Other language preserved');
$store->save("quoted'code",'fixture',['en'=>"A 'quote' & slash \\"]);checkTranslation(isset($store->page('en','fixture')["quoted'code"]),'Quoted identifier value stored');
$store->save('separate',null,['en'=>'Null page']);
$store->save('separate','',['en'=>'Empty page']);
checkTranslation($store->page('en',null)['separate']==='Null page'&&$store->page('en','')['separate']==='Empty page','Null and empty pages remain distinct');
$store->addLang(['lang_id'=>'pt_br','name'=>'Portuguese']);
$store->save('unicode','fixture',['pt_br'=>'Olá 中文 😀']);
checkTranslation($store->page('pt_br','fixture')['unicode']==='Olá 中文 😀','Regional identifier and new-table Unicode');
$store->removeLang('pt_br',true);
$store->addLang(['lang_id'=>'de','name'=>'German']);
sql('ALTER TABLE tbl_de ENGINE=MyISAM');
try{$store->save('unsafe','fixture',['en'=>'Must not insert','de'=>'No transaction']);throw new LogicException('Nontransactional table accepted');}catch(RuntimeException $e){}
checkTranslation(!isset($store->page('en','fixture')['unsafe']),'Nontransactional write fails before changing any language');
$store->removeLang('de',true);
$store->save('zero','fixture',['en'=>'0']);checkTranslation($t->get('zero')==='0','Zero retained');
$store->save('only_english','fixture',['en'=>'Fallback']);checkTranslation($t->get('only_english','fixture','fr')==='Fallback','English fallback');
$store->updateLang(['lang_id'=>'fr','name'=>'French updated']);checkTranslation($t->getLangs()['fr']==='French updated','Metadata/cache update');
sql("ALTER TABLE tbl_fr ADD CONSTRAINT fixture_failure CHECK (fr <> 'FAIL')");
try{$store->save('greeting','fixture',['en'=>'Must roll back','fr'=>'FAIL']);throw new LogicException('Write should fail');}catch(RuntimeException $e){}
checkTranslation($t->get('greeting')==='Changed'&&$store->page('en','fixture')['greeting']==='Changed','Atomic rollback preserves English');
checkTranslation(in_array('fixture',$store->getPageNames(),true),'Page list');
foreach(['../en','en; DROP TABLE tbl_en','EN'] as $invalid){try{$store->save('x','fixture',[$invalid=>'bad']);throw new LogicException('Invalid ID accepted');}catch(InvalidArgumentException $e){}}
$store->removeLang('fr');checkTranslation(!isset($store->languages()['fr']),'Unregistered');
$store->addLang(['lang_id'=>'fr','name'=>'French']);checkTranslation($t->get('greeting','fixture','fr')==='Bonjour','Re-registration preserves translations');
$store->removeLang('fr',true);checkTranslation(!isset($store->languages()['fr']),'Explicit destroy');
try{$store->removeLang('en',true);throw new LogicException('English removed');}catch(InvalidArgumentException $e){}
foreach(['empty'=>'','spaces'=>'   ','padded'=>"  é 中文 \n"] as $id=>$text) {
    $store->save($id,'exact',['en'=>$text]);
    checkTranslation($store->page('en','exact')[$id]===$text,'Exact translation text changed');
}
checkTranslation($store->selectPrepared("SELECT ' x ' AS PADDED, '' AS EMPTY")===[['padded'=>' x','empty'=>null]],'Exact reads changed the legacy portability setting');
try {$store->selectExactPrepared('SELECT missing_fixture_column FROM tbl_en');throw new LogicException('Invalid query accepted');}catch(RuntimeException $e){}
checkTranslation($store->selectPrepared("SELECT ' x ' AS PADDED")===[['padded'=>' x']],'Failed exact query leaked portability settings');
$store->transaction(function() use ($store) {
    try {$store->addLang(['lang_id'=>'es','name'=>'Spanish']);throw new LogicException('DDL nested in transaction');}catch(RuntimeException $e){}
    checkTranslation($store->hasActiveTransaction(),'Refused language DDL ended caller transaction');
});
checkTranslation(!isset($store->languages()['es']),'Refused language creation changed registry');
sql('CREATE TABLE tbl_it (unexpected INT) ENGINE=InnoDB');
try {$store->addLang(['lang_id'=>'it','name'=>'Italian']);throw new LogicException('Incompatible retained table accepted');}catch(RuntimeException $e){}
checkTranslation(!isset($store->languages()['it']),'Incompatible table registered');
sql('DROP TABLE tbl_it');
$workers=[];
for($i=0;$i<2;$i++){$process=proc_open([PHP_BINARY,'-d','display_errors=1','-d','auto_prepend_file=',__FILE__,$app,'worker'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,null,array_merge(getenv(),['TRANSLATION_DRIVER'=>$i===0?'MDB2':'PDO']));$workers[]=[$process,$pipes];}
foreach($workers as [$process,$pipes]){$output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);checkTranslation(proc_close($process)===0&&$output==='worker-ok'&&$error==='','Concurrent writer failed');}
$row=sql("SELECT COUNT(*) AS total FROM tbl_en WHERE id='concurrent' AND pageID='fixture'")[0];
checkTranslation((int)$row['total']===1,'Concurrent inserts duplicated a language item');
checkTranslation(!class_exists('Translation2',false),'Translation2 not loaded');
if($driver==='PDO')checkTranslation(!class_exists('MDB2',false)&&!class_exists('PEAR',false),'Native translation path loaded MDB2/PEAR');
echo "PASS: real database lookup, Unicode, language creation/update/re-registration/removal, isolated writes, cache invalidation, rollback, mixed-driver two-process concurrency, exact whitespace/empty text, nested transaction refusal, injection rejection and fallback protection\n";
