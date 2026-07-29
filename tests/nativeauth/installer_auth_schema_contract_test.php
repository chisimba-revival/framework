<?php
declare(strict_types=1);

function assertContract(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__, 2);
$sqlDir = $root . '/app/core_modules/security/sql';
$expected = array(
    'tbl_auth_mfa_enrolments' => array(
        'fields' => array('id', 'user_id', 'factor_type', 'encrypted_secret',
            'secret_nonce', 'enrolled_at', 'verified_at',
            'last_accepted_step', 'disabled_at'),
        'indexes' => array('auth_mfa_enrolments_primary',
            'auth_mfa_user_factor')
    ),
    'tbl_auth_mfa_recovery_codes' => array(
        'fields' => array('id', 'user_id', 'code_hash', 'created_at', 'used_at'),
        'indexes' => array('auth_mfa_recovery_primary',
            'auth_mfa_recovery_user')
    ),
    'tbl_auth_persistent_logins' => array(
        'fields' => array('id', 'user_id', 'selector', 'verifier_hash',
            'issued_at', 'expires_at', 'last_used_at', 'revoked_at',
            'replaced_by_id'),
        'indexes' => array('auth_persistent_primary',
            'auth_persistent_selector', 'auth_persistent_user',
            'auth_persistent_expiry')
    )
);

foreach ($expected as $expectedTable => $contract) {
    unset($tablename, $options, $fields, $name, $indexes, $tableIndexes);
    include $sqlDir . '/' . $expectedTable . '.sql';
    assertContract($tablename === $expectedTable, "{$expectedTable}: table name");
    assertContract(array_keys($fields) === $contract['fields'],
        "{$expectedTable}: fields");
    assertContract(array_keys($tableIndexes) === $contract['indexes'],
        "{$expectedTable}: indexes");
    assertContract(($tableIndexes[$contract['indexes'][0]]['primary'] ?? FALSE) === TRUE,
        "{$expectedTable}: primary index");
}

$modulesAdmin = file_get_contents(
    $root . '/app/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php'
);
assertContract(substr_count($modulesAdmin, '$tableCreated = $this->makeTable($table);') === 1,
    'installModule calls makeTable once');
assertContract(strpos($modulesAdmin, 'table creation failed miserably') === FALSE,
    'legacy fatal output removed');
assertContract(strpos($modulesAdmin, 'foreach ($tableIndexes as $indexName => $indexDefinition)') !== FALSE,
    'named index collection supported');


$registerFile = $root . '/app/core_modules/security/register.conf';
$registerLines = file($registerFile, FILE_IGNORE_NEW_LINES);
$tableDeclarations = array_values(array_filter(
    $registerLines,
    static function (string $line): bool {
        return strpos($line, 'TABLE:') === 0;
    }
));
foreach ($tableDeclarations as $declaration) {
    assertContract(strpos($declaration, '|') === FALSE,
        "register.conf TABLE declarations contain only canonical table names");
}
assertContract(
    in_array('TABLE: tbl_auth_persistent_logins', $tableDeclarations, TRUE),
    'persistent-login table is registered canonically'
);

echo "PASS: authentication installer schema contract\n";
