# Chisimba UI Core Module

The `ui` core module provides framework-independent user interface components.

Initial usage:

```php
$window = $this->getObject('window', 'ui');

$window->setTitle('Student details');
$window->setWidth(700);
$window->setContent($content);

echo $window->showOpenButton('Open');
echo $window->show();
```

The first component is intentionally small and native. It uses semantic HTML,
CSS, and the browser `dialog` API.

The module does not yet replace or modify ExtJS. It establishes the target API
before any legacy caller is migrated.
