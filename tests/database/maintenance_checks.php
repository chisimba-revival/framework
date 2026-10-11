<?php
/** Real dbTable maintenance against the runner's disposable schema, on both drivers. */
require_once '/modules/sitepages/classes/sitepagesmaintenance_class_inc.php';
require_once '/modules/payment-service/classes/paymentmaintenance_class_inc.php';
$maintenance = static function ($class) use ($db, $table) {
    $service = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(dbTable::class, '_db'))->setValue($service, $db);
    $service->dbLayer = $table->dbLayer;
    $service->dbType = 'mysqli';
    return $service;
};
$pages = $maintenance('sitepagesmaintenance');
$payments = $maintenance('paymentmaintenance');
foreach (['tbl_sitepages','tbl_payment_service_intents','tbl_payment_service_prices'] as $name) $table->executeExactPrepared('DROP TABLE IF EXISTS '.$name);
try { $pages->reconcile(true); throw new Exception('Missing page table accepted'); } catch (RuntimeException $expected) {}
$table->executeExactPrepared('CREATE TABLE tbl_sitepages (id INT PRIMARY KEY, body_html LONGTEXT) ENGINE=InnoDB');
$table->executeExactPrepared('INSERT INTO tbl_sitepages VALUES (?,?)', [1,"Keep ’ quoted <p>content</p>"]);
ensureBaseline(count($pages->reconcile(false))===1,'Page dry-run plan missing');
ensureBaseline(!in_array('composition_json',(new ChisimbaDatabaseSchema($table))->columns('tbl_sitepages'),true),'Dry run wrote page schema');
$table->beginTransaction();
try { $pages->reconcile(true); throw new Exception('Page DDL accepted inside transaction'); } catch (LogicException $expected) {}
ensureBaseline($table->hasActiveTransaction(),'DDL guard ended caller transaction');
$table->rollbackTransaction();
$pages->reconcile(true);
ensureBaseline($pages->reconcile(true)===[],'Page maintenance is not idempotent');
ensureBaseline($table->selectExactPrepared('SELECT body_html FROM tbl_sitepages')[0]['body_html']==="Keep ’ quoted <p>content</p>",'Page content changed');
$table->executeExactPrepared('ALTER TABLE tbl_sitepages ENGINE=MyISAM');
ensureBaseline(count($pages->reconcile(false))===1,'Nontransactional page engine not detected');
$pages->reconcile(true);(new ChisimbaDatabaseSchema($table))->requireTransactional('tbl_sitepages');
try { $payments->reconcile(true); throw new Exception('Missing payment tables accepted'); } catch (RuntimeException $expected) {}
$table->executeExactPrepared('CREATE TABLE tbl_payment_service_intents (id INT PRIMARY KEY, user_id VARCHAR(25) NOT NULL) ENGINE=InnoDB');
$table->executeExactPrepared('CREATE TABLE tbl_payment_service_prices (id INT PRIMARY KEY, amount_minor INT NOT NULL) ENGINE=InnoDB');
$table->executeExactPrepared('INSERT INTO tbl_payment_service_intents VALUES (1,?)',['preserved-owner']);
$table->executeExactPrepared('INSERT INTO tbl_payment_service_prices VALUES (1,12345)');
ensureBaseline(count($payments->reconcile(false))===2,'Payment dry-run plan missing');
ensureBaseline(!in_array('vat_minor',(new ChisimbaDatabaseSchema($table))->columns('tbl_payment_service_prices'),true),'Payment dry run wrote schema');
$table->beginTransaction();
try { $payments->reconcile(true); throw new Exception('Payment DDL accepted inside transaction'); } catch (LogicException $expected) {}
$table->rollbackTransaction();
$payments->reconcile(true);
ensureBaseline($payments->reconcile(true)===[],'Payment maintenance is not idempotent');
ensureBaseline($table->selectExactPrepared('SELECT user_id FROM tbl_payment_service_intents')[0]['user_id']==='preserved-owner','Payment identity changed');
$price=$table->selectExactPrepared('SELECT amount_minor,vat_minor FROM tbl_payment_service_prices')[0];
ensureBaseline((int)$price['amount_minor']===12345 && (int)$price['vat_minor']===0,'Historical price/VAT changed');
$table->executeExactPrepared('INSERT INTO tbl_payment_service_intents VALUES (2,NULL)');
echo "PASS: maintenance check/apply/repeat, content preservation, missing-schema failures and transaction ownership on ".$table->dbLayer."\n";
