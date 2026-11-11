<?php
/** Test native dbTable contracts on disposable synthetic data, never a site.
 * @author Derek Keats
 */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
$app = '/framework/app';
set_include_path($app . PATH_SEPARATOR . get_include_path());
require_once $app . '/classes/core/chisimbadatabaseconnection.php';
$db = ChisimbaDatabaseConnection::fromLegacyDsn(['phptype' => 'mysqli', 'hostspec' => 'baseline-db:3306',
    'username' => 'root', 'password' => getenv('CHISIMBA_DB_PASSWORD'), 'database' => 'contract_fixture']);
$GLOBALS['kewl_entry_point_run'] = true;
// Only object construction/configuration is bypassed. All database operations use
// the real source dbTable and native PDO boundary, with caches disabled by default.
class ChisimbaObject {public function getObject($name,$module=''){return $GLOBALS['pathObjects'][$name];}}
require_once $app . '/classes/core/dbtable_class_inc.php';
$table = new dbTable();
(new ReflectionProperty(dbTable::class, '_db'))->setValue($table, $db);
$table->dbLayer = 'PDO';
$table->dbType = 'mysqli';
$table->_tableName = 'pdo_contract_rows';
$table->_serverName = 'fixture';
function ensureBaseline(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
ensureBaseline($table->query('CREATE TABLE pdo_contract_rows (puid INT AUTO_INCREMENT PRIMARY KEY, id VARCHAR(64) UNIQUE, body TEXT NULL, amount DECIMAL(10,2), quantity INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3') !== false, 'fixture schema failed');
function injectCallerTable(dbTable $caller, $name): void {
 global $db,$table;(new ReflectionProperty(dbTable::class,'_db'))->setValue($caller,$db);
 $caller->dbLayer='PDO';$caller->dbType='mysqli';$caller->_tableName=$name;$caller->_serverName='fixture';
}

require '/modules/simpleblog/classes/publishingstore_class_inc.php';
require '/modules/simpleblog/patches/installscripts_class_inc.php';
require '/framework/app/core_modules/security/classes/userservice_class_inc.php';
require '/modules/webinar/classes/webinarstore_class_inc.php';
// Rehearse the actual additive migration twice, retaining historical attribution.
$table->executePrepared("CREATE TABLE tbl_simpleblog_posts (id VARCHAR(32) PRIMARY KEY,author_credit VARCHAR(250),userid VARCHAR(25),post_content TEXT) ENGINE=InnoDB");
$table->executePrepared("INSERT INTO tbl_simpleblog_posts VALUES (?,?,?,?)",['post','Original author','owner','Original body']);
$xml=simplexml_load_file('/modules/simpleblog/sql/sql_updates.xml');
foreach ([1,2] as $pass) foreach($xml->xpath('/updates/update[version="0.084"]/SQL') as $sql)$table->executePrepared((string)$sql);
$row=$table->selectPrepared('SELECT * FROM tbl_simpleblog_posts WHERE id=?',['post'])[0];
ensureBaseline($row['author_credit']==='Original author' && $row['userid']==='owner' && $row['author_kind']===null && $row['author_id']===null,'Repeat migration preserves original attribution and ownership');
$posts=new publishingstore();injectCallerTable($posts,'tbl_simpleblog_posts');
$posts->begin();$posts->persist('post',['author_credit'=>'Guest','author_kind'=>'user','author_id'=>'guest']);$posts->rollback();
ensureBaseline($posts->post('post')['author_credit']==='Original author','Credit rolls back with post');
$posts->begin();$posts->persist('post',['author_credit'=>'Guest','author_kind'=>'user','author_id'=>'guest']);$posts->commit();
ensureBaseline($posts->post('post')['userid']==='owner' && $posts->post('post')['author_id']==='guest','Credit commits independently of owner');
$table->executePrepared('CREATE TABLE tbl_users (userid VARCHAR(25),firstname VARCHAR(100),surname VARCHAR(100),isactive INT) ENGINE=InnoDB');
foreach([['u1','Pat','Bird',1],['u2','Pat','Bird',1],['u3','Pat','Hidden',0]] as $values)$table->executePrepared('INSERT INTO tbl_users VALUES (?,?,?,?)',$values);
$users=new userservice();injectCallerTable($users,'tbl_users');
$matches=$users->authorNameSuggestions('Pat');ensureBaseline(count($matches)===2,'Active duplicate names are separate candidates');
ensureBaseline(array_keys($matches[0])===['userid','firstname','surname'],'Only name and logical ID returned');
ensureBaseline($users->authorNameSuggestions("%' OR 1=1 --")===[],'Bounded literal search does not inject SQL or match wildcards');
$table->executePrepared('CREATE TABLE tbl_webinar_records (id VARCHAR(32),title VARCHAR(250),kind VARCHAR(20),status VARCHAR(20)) ENGINE=InnoDB');
foreach([['s1','Pat Bird','speaker','published'],['s2','Pat Hidden','speaker','draft'],['w1','Pat Talk','webinar','published']] as $values)$table->executePrepared('INSERT INTO tbl_webinar_records VALUES (?,?,?,?)',$values);
$speakers=new webinarstore();injectCallerTable($speakers,'tbl_webinar_records');ensureBaseline($speakers->speakerNameSuggestions('Pat')===[['id'=>'s1']],'Only published speaker identities are returned');
echo "PASS PDO credit migration, repeatability, rollback, ownership preservation and private-safe name queries\n";
