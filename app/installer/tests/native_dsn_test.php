<?php
/** Repair settings must retain credentials, ports and sockets without PEAR DB. */
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
require dirname(__DIR__,2).'/classes/core/chisimbadatabasedsn.php';
require dirname(__DIR__,2).'/classes/core/chisimbadatabaseconnection.php';
$cases = [
 ['mysqli://user:p%40ss%3Aword@localhost:3307/course', ['username'=>'user','password'=>'p@ss:word','database'=>'course'], 'localhost:3307'],
 ['mysql://u:p@tcp(db:3308)/course', ['hostspec'=>'db','port'=>'3308'], 'db:3308'],
 ['mysql://u:p@tcp+db:3308/course', ['hostspec'=>'db','port'=>'3308'], 'db:3308'],
 ['mysqli://u:p@unix(/run/mysql.sock)/course', ['protocol'=>'unix','socket'=>'/run/mysql.sock'], 'unix(/run/mysql.sock)'],
 ['mysqli://u:p@unix+%2Frun%2Fmysql.sock/course', ['socket'=>'/run/mysql.sock'], 'unix(/run/mysql.sock)'],
 ['mysqli://u:p@[::1]:3309/course', ['port'=>'3309'], '[::1]:3309'],
 ['mysqli://localhost/course%3Fone?charset=utf8&password=bad', ['database'=>'course?one','password'=>''], 'localhost'],
 ['mysql', ['phptype'=>'mysql','dbsyntax'=>'mysql','username'=>'','password'=>''], ''],
 ['mysql(pgsql)://u@db/course', ['dbsyntax'=>'pgsql','password'=>''], 'db'],
 [['phptype'=>'mysqli','hostspec'=>'db','port'=>3307,'password'=>false], ['dbsyntax'=>'mysqli','password'=>''], 'db:3307'],
];
foreach($cases as [$input,$expected,$location]) {
 $actual=ChisimbaDatabaseDsn::forInstaller($input);
 foreach($expected as $key=>$value)if($actual[$key]!==$value)throw new RuntimeException('Mismatch '.$key);
 if(ChisimbaDatabaseDsn::installerLocation($actual)!==$location)throw new RuntimeException('Lost connection location');
}
foreach (['db:3307'=>'mysql:host=db;port=3307;dbname=course;charset=utf8',
    'unix(/run/mysql.sock)'=>'mysql:unix_socket=/run/mysql.sock;dbname=course;charset=utf8',
    '[::1]:3308'=>'mysql:host=::1;port=3308;dbname=course;charset=utf8'] as $location=>$expected) {
    $saved='mysqli://u:p@'.$location.'/course';
    $fields=ChisimbaDatabaseDsn::forInstaller($saved);
    $rebuilt='mysqli://u:p@'.ChisimbaDatabaseDsn::installerLocation($fields).'/course';
    if(ChisimbaDatabaseConnection::mysqlDsn(ChisimbaDatabaseDsn::parse($rebuilt))!==$expected)throw new RuntimeException('PDO roundtrip changed destination');
}
if(class_exists('DB',false)||class_exists('PEAR',false))throw new RuntimeException('Unexpected PEAR bootstrap');
foreach(['installwizard.inc','steps/secondarydetails.inc'] as $path) {
 $source=file_get_contents(dirname(__DIR__).'/'.$path);
 if(str_contains($source,'DB::parseDSN')||str_contains($source,"require_once 'DB.php'"))throw new RuntimeException('Installer PEAR caller returned');
}
echo "PASS native installer DSN settings: defaults, encoded credentials, ports, IPv6, sockets, array input, query isolation\n";
