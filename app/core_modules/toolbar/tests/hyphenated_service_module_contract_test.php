<?php
$root=dirname(__DIR__);$files=array($root.'/controller.php',$root.'/classes/dbmenu_class_inc.php');$fail=array();
foreach($files as $file){$source=file_get_contents($file);if(strpos($source,"[a-z0-9_-]{0,63}")===false){$fail[]=$file.' rejects service-module identifiers';}}
$storage=file_get_contents($root.'/classes/dbmenu_class_inc.php');
if(strpos($storage,'$this->_db = $this->objEngine->getDbObj();')===false){$fail[]='toolbar menu storage database handle is not initialised';}
if($fail){fwrite(STDERR,implode("\n",$fail)."\n");exit(1);}echo "PASS: toolbar accepts bounded hyphenated service-module identifiers.\n";
?>
