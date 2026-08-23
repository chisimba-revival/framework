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

## Icons

The module vendors the complete Lucide 1.28.0 SVG catalogue. `iconservice`
accepts only bounded, lowercase kebab-case names and resolves them exclusively
inside the bundled catalogue; no URL, path traversal, JavaScript, or CDN lookup
is permitted. Availability and design choice are deliberately separate:
modules should still choose one stable semantic icon for each action.
