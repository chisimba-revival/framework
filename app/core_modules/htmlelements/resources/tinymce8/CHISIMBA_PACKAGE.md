# TinyMCE package used by Chisimba

- Upstream package: `tinymce`
- Version: `8.8.0`
- Installation source: npm registry
- Chisimba integration: `htmlelements/editoradapter`
- Licence selection: `license_key: 'gpl'`

Application modules must not instantiate TinyMCE directly. They must continue
using Chisimba's `htmlelements/htmlarea` compatibility boundary.
