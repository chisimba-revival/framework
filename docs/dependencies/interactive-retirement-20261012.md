# Interactive Flash and Java retirement — 12 October 2026

Active Knowledge Maps replaces FreeMind viewing. Remove the bundled Flash FreeMind viewer and Java OBJ applet. File Manager, MAP shortcodes, Discussion and Sitemap retain original-file/export links through the shared safe nativeplayer helper. Existing maps are not converted or imported automatically. File Manager audio, video and 3D preview callers use the authorised linkname route, not storage paths. Discussion map templates now provide pageContent to the current layout rather than the obsolete middleColumn variable.

Retire LiveChat and RTT Java clients in the coordinated modules release. Read-only registration checks found neither registered on local, chisimba.com, KengaLearn, LearnTheBirds, KengaPub, KengaSolutions or Heritage. Remove the context-home LiveChat hook; RTT shortcodes remain unchanged text without loading a service. Preserve historical database tables, records, stored content and original uploads. Git history retains the retired sources.

Local catalogue updates: files 0.439, filters 1.567, filemanager 1.108, context 2.054, discussion 3.035. Sitemap 0.011 remains uninstalled locally.

Validation: PHP lint and diff checks; retired_interactive_viewers_test, retired_remote_filters_test, retired_media_test, modules retired_java_clients_test, all five File Manager tests, file-access policy suite (50 checks plus revoked-access checks). Browser: existing Active Knowledge Maps editor loads; Discussion map page displays the original export link. Local PHP logs contain no new warning/deprecation/fatal messages during these checks.

Production deployment remains pending. Deploy framework and modules together, repeat registration checks before pruning retired client directories, and apply normal catalogue updates. No database tables or user uploads should be dropped. Remaining Runway/TinyMCE Flash and other Java assets require separate caller tracing; this is not a claim that every Flash/Java reference is gone. ExtJS and jQuery are separate migrations.
