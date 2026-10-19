<?php
/** Preserve Lucene text encoding while accepting absent optional metadata. */
require __DIR__ . '/strict_warning_bootstrap.php';
$app = $argv[1] ?? dirname(__DIR__, 2) . '/app';
require $app . '/core_modules/search/resources/1.7.3/Search/Lucene/Field.php';
foreach (['UTF-8', 'ISO-8859-1'] as $encoding) {
    $field = Zend_Search_Lucene_Field::UnIndexed('optional', null, $encoding);
    if ($field->getUtf8Value() !== '') throw new RuntimeException('Absent metadata must be empty text');
}
$field = Zend_Search_Lucene_Field::Text('title', "Caf\xe9", 'ISO-8859-1');
if ($field->getUtf8Value() !== 'Café') throw new RuntimeException('Latin-1 conversion changed');
$field = Zend_Search_Lucene_Field::Text('title', 'Café 🌱', 'UTF-8');
if ($field->getUtf8Value() !== 'Café 🌱') throw new RuntimeException('UTF-8 text changed');
echo "PASS: strict Lucene optional metadata and Latin-1/UTF-8 conversion\n";
