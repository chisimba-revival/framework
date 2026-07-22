<?php
/**
 * Native UI component demonstration.
 *
 * @category Chisimba
 * @package  ui
 * @author   Derek Keats
 */

$window = $this->getObject('window', 'ui');

$window->setId('chisimba-ui-demo-window')
    ->setTitle('Chisimba Native Window')
    ->setWidth(640)
    ->setContent(
        '<p>This window is rendered through the Chisimba UI component layer.</p>'
        . '<p>It has no dependency on ExtJS.</p>'
    );

echo '<h1>Chisimba UI demonstration</h1>';
echo $window->showOpenButton('Open native window');
echo $window->show();

echo '<section aria-labelledby="native-messagebox-demo-title">';
echo '<h2 id="native-messagebox-demo-title">Native message boxes</h2>';

$nativeMessagebox = $this->getObject('messagebox', 'ui');

echo $nativeMessagebox->show(
    'The native UI foundation is working without ExtJS.',
    'success',
    'Success',
    true,
    'native-ui-success'
);

echo $nativeMessagebox->show(
    'This warning uses semantic alert behaviour and a keyboard-operable dismiss button.',
    'warning',
    'Accessibility demonstration',
    true,
    'native-ui-warning'
);

echo '</section>';
