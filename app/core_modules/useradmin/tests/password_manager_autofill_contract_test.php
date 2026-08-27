<?php
$template = file_get_contents(
    dirname(__DIR__) . '/templates/content/native_admin_tpl.php'
);
$expect = static function ($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$expect(
    str_contains($template, 'data-lpignore="true"')
        && str_contains($template, 'data-1p-ignore')
        && str_contains($template, 'data-bwignore'),
    'Administrative user editing must opt out of password-manager login autofill.'
);
$expect(
    str_contains($template, 'name="username" required autocomplete="new-username"'),
    'The edited account username must not be treated as the administrator login.'
);
$expect(
    substr_count($template, 'data-form-type="other"') >= 4,
    'Identity and password fields must be identified as administration data.'
);

fwrite(STDOUT, "PASS: user administration resists credential autofill\n");
?>
