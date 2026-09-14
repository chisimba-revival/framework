<?php
/** XML callbacks, error handling and Config fidelity under strict PHP diagnostics. */
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$app=$argv[1]??dirname(__DIR__,2).'/app';
set_include_path($app.'/lib/pear'.PATH_SEPARATOR.get_include_path());
require_once 'XML/Parser.php';
require_once 'XML/Parser/Simple.php';
require_once 'Config.php';
require_once 'Config/Container/XML.php';
function checkXml($ok,$message){if(!$ok)throw new RuntimeException($message);}
class XmlAuditHandler {
 public $events=[],$text='';
 public function startHandler($parser,$name,&$attributes){$this->events[]=['start',$name,$attributes];$attributes['local']='allowed';}
 public function endHandler($parser,$name){$this->events[]=['end',$name];}
 public function cdataHandler($parser,$text){$this->text.=$text;}
 public function xmltag_ROOT($parser,$name,$attributes){$this->events[]=['func',$name,$attributes];}
 public function xmltag_ROOT_($parser,$name){$this->events[]=['func-end',$name];}
}
$h=new XmlAuditHandler();$p=new XML_Parser('UTF-8');$p->setHandlerObj($h);
checkXml($p->parseString('<root a="one &amp; two">caf',false)===true,'Partial parse');
checkXml($p->parseString('é<![CDATA[ <birds> ]]></root>',true)===true,'Final parse');
checkXml($h->events[0]===['start','ROOT',['A'=>'one & two']],'Attributes, case folding and external handler');
checkXml($h->text==='café <birds> ','UTF-8, CDATA and split character data');
checkXml($p->parser===null,'Parser released after successful parse');
checkXml(PEAR::isError($p->parseString('<root><bad></root>',true)),'Malformed XML returns PEAR error');
checkXml($p->parser===null,'Parser released after parse failure');
checkXml($p->parseString('<root/>',true)===true,'Parser reusable after failure');
$p->free();$p->free();
$functions=new XmlAuditHandler();$f=new XML_Parser('UTF-8','func');$f->setHandlerObj($functions);
checkXml($f->parseString('<root>text</root>',true)===true,'Function-mode parse');
checkXml($functions->events===[['func','ROOT',[]],['func-end','ROOT']] && $functions->text==='text','Function and character callbacks');
class XmlAuditSimple extends XML_Parser_Simple {
 public $elements=[];
 public function handleElement($name,$attributes,$data){$this->elements[]=[$name,$attributes,$data];}
 public function handleElement_ROOT($name,$attributes,$data){$this->handleElement($name,$attributes,$data);}
}
foreach(['event','func'] as $mode){
 $simple=new XmlAuditSimple('UTF-8',$mode);
 checkXml($simple->parseString('<root a="1">café</root>',true)===true,'Simple parser '.$mode);
 checkXml($simple->elements===[['ROOT',['A'=>'1'],'café']],'Simple parser preserves attributes and character data');
}
$config=new Config();$reader=new Config_Container_XML();
$reader->Config_Container_XML(['isFile'=>false,'encoding'=>'UTF-8']);
checkXml($reader->parseDatasrc('<settings><title lang="en">Café &amp; birds</title><empty/><item>one</item><item>two</item></settings>',$config)===true,'Config parse');
$root=$config->container->children[0];
checkXml($root->name==='settings' && count($root->children)===4,'Configuration hierarchy and duplicate elements');
checkXml($root->children[0]->content==='Café & birds' && $root->children[0]->attributes===['lang'=>'en'],'Configuration values and attributes');
checkXml($root->children[1]->content==='','Empty element is an empty string');
echo "PASS: strict XML event/function handlers, reference compatibility, attributes, Unicode, CDATA, empty elements, malformed input and reuse\n";
