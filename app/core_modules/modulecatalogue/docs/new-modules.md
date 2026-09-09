# Discovering modules

New modules shows locally available modules first released less than 30 days ago, including installed modules. Not installed shows all available modules still needing installation, regardless of age. Counts cover the whole local catalogue. Search, categories and Refresh catalogue retain the filter.

## Module authors and future agents

Add MODULE_CREATEDATE: YYYY-MM-DD to each new module's register.conf using its original first-release date. Never change this date on updates. Continue updating MODULE_RELEASEDATE separately. Unknown, invalid and future creation dates are excluded from New. Do not use file timestamps or deployment dates.

Recent creation dates were recovered from each original register.conf in Git history; restored legacy modules were excluded. Metadata is read from source and requires no module installation or database migration. Deploy these module manifests with Module Catalogue 3.136 and apply the catalogue update to register its labels.
