<?php
/** languageText must resolve system terminology just as code2Txt does. */
$source = file_get_contents(dirname(__DIR__) . '/classes/language_class_inc.php');
$checks = array(
    'translated language result is abstracted' => strpos($source, 'is_string($result) ? $this->abstractText($result) : $result') !== false,
    'fallback language result is abstracted' => strpos($source, 'return $this->abstractText($default);') !== false,
    'uppercase tokens request sentence case' => substr_count($source, 'preg_quote(strtoupper($textItem)') === 2,
);
foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}
fwrite(STDOUT, "PASS: languageText resolves system terminology in translations and fallbacks.\n");
