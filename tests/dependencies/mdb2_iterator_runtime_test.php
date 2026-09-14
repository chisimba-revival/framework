<?php
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$app=$argv[1]??dirname(__DIR__,2).'/app';set_include_path($app.'/lib/pear'.PATH_SEPARATOR.get_include_path());
require_once 'MDB2.php';require_once 'MDB2/Iterator.php';
class IteratorResultProbe extends MDB2_Result_Common {
 public $rows,$position=0,$reads=0,$frees=0,$failed=false;
 public function __construct($rows){$this->rows=$rows;}
 public function fetchRow($fetchmode=MDB2_FETCHMODE_DEFAULT,$rownum=null){$this->reads++;return $this->failed?new MDB2_Error(MDB2_ERROR):($this->rows[$this->position++]??null);}
 public function seek($rownum=0){$this->position=$rownum;return MDB2_OK;}
 public function rowCount(){return $this->position;}
 public function numRows(){return count($this->rows);}
 public function valid(){return $this->failed?new MDB2_Error(MDB2_ERROR):$this->position<count($this->rows);}
 public function free(){$this->frees++;return MDB2_OK;}
}
function checkIterator($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach([MDB2_Iterator::class,MDB2_BufferedIterator::class] as $class){
 $result=new IteratorResultProbe([['name'=>'first'],['name'=>'second']]);$iterator=new $class($result);
 checkIterator($iterator->valid(),'Valid first row');
 checkIterator($iterator->current()===['name'=>'first'],'First row');$reads=$result->reads;
 checkIterator($iterator->current()===['name'=>'first'] && $result->reads===$reads,'Repeated current must not advance');
 checkIterator($iterator->key()===1,'Driver row key preserved');$iterator->next();
 checkIterator($iterator->valid() && $iterator->current()===['name'=>'second'],'Second row');$iterator->next();
 checkIterator($iterator->valid()===false,'End of rows');
 $iterator->seek(0);checkIterator($iterator->current()===['name'=>'first'],'Seek clears cached row');
 unset($iterator);checkIterator($result->frees===1,'Destructor frees the result');
 $empty=new $class(new IteratorResultProbe([]));checkIterator(iterator_to_array($empty)===[],'Empty result');
 $rows=new $class(new IteratorResultProbe([['id'=>1],['id'=>2]]));
 checkIterator(array_values(iterator_to_array($rows))===[['id'=>1],['id'=>2]],'foreach returns every row once');
 if($class===MDB2_BufferedIterator::class)checkIterator(count(iterator_to_array($rows))===2 && $rows->count()===2,'Buffered rewind/count');
 $failure=new IteratorResultProbe([]);$failure->failed=true;$failed=new $class($failure);
 checkIterator($failed->valid()===false,'Database errors stop iteration');
}
echo "PASS: strict iterator signatures, empty/multi-row traversal, cached current, keys, seek, buffered rewind/count, cleanup and database errors\n";
