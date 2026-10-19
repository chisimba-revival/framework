<?php
/** Long installer server IDs must not overflow canonical VARCHAR(32) keys. */
$table->executeExactPrepared('CREATE TABLE generated_id_fixture (id VARCHAR(32) PRIMARY KEY, body VARCHAR(64)) ENGINE=InnoDB');
$originalName = $table->_serverName;
try {
    foreach (['pear-test-20261012', str_repeat('a', 200), '鳥類の観察サイト'] as $name) {
        $table->_serverName = $name;
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $id = $table->insert(['body'=>'synthetic'], 'generated_id_fixture');
            if (!is_string($id) || strlen($id) > 32 || isset($ids[$id])) throw new RuntimeException('Generated ID overflow or collision');
            $ids[$id] = true;
        }
    }
    if ($table->insert(['id'=>'explicit-id', 'body'=>'preserved'], 'generated_id_fixture') !== 'explicit-id') {
        throw new RuntimeException('Explicit ID changed');
    }
} finally {
    $table->_serverName = $originalName;
    $table->executeExactPrepared('DROP TABLE generated_id_fixture');
}
echo "PASS generated IDs: long/Unicode server names fit VARCHAR(32), explicit IDs preserved\n";
