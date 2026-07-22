# Chisimba rich-text editor boundary

The supported server-side rich-text editor API is the `htmlarea` class in the
`htmlelements` core module:

```php
$this->newObject('htmlarea', 'htmlelements');
$this->getObject('htmlarea', 'htmlelements');
$this->loadClass('htmlarea', 'htmlelements');
```

`htmlarea` is retained as a compatibility name. It is not a commitment to the
obsolete HTMLArea JavaScript editor.

The intended architecture is:

```text
application module
    -> htmlelements/htmlarea
        -> editor adapter
            -> selected modern editor
```

New first-party Chisimba code must not instantiate or load CKEditor,
FCKeditor, TinyMCE, Xinha, HTMLArea, markItUp, or ExtJS HtmlEditor directly.

Historical direct integrations identified by the July 2026 audit remain
explicit migration items. They are recorded in
`editor-direct-usage-baseline.txt` so the regression guard can detect new
direct integrations without confusing bundled vendor code with callers.
