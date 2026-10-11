# Media metadata regression

The Files module pins unmodified upstream getID3 1.9.27. Provenance, archive digest
and per-file SHA-256 values are in `app/core_modules/files/resources/getid3/UPSTREAM.json`.
The vendor payload includes upstream parser/writer modules and licences, but no
web demos, helper executables or upstream test media. Old 1.7.5 files are replaced,
not overlaid. The Chisimba wrapper uses UTF-8 plain-text tags without changing PHP
error reporting. File Manager excludes raw binary metadata chunks from its text
XML, and uses getID3's Ogg detection instead of its obsolete secondary parser.

Generate entirely synthetic fixtures with Python and ffmpeg:

```
python3 tests/media/make-fixtures.py /tmp/chisimba-media-fixtures
php tests/media/getid3_test.php /tmp/chisimba-media-fixtures
```

Run with the site's PHP version and extensions (PHP 8.5 was validated). Tests cover
WAV, MP3, FLAC, Ogg/Vorbis, PNG, JPEG, MP4, Unicode titles, image dimensions, Ogg
MIME/sample rate, native XML, invalid/missing files and unchanged error reporting.
They do not establish support for every codec or every malformed media input.

After applying normal Files/File Manager catalogue updates to a disposable fresh
installation, test real object composition without mail or database mutations:

```
CHISIMBA_INSTALLER_FIXTURE=1 php tests/media/installed_media_smoke.php /installed/ch /tmp/chisimba-media-fixtures
```

Browser validation on 11 October 2026: synthetic MP3 uploaded through File Manager
on the disposable PDO site, listed as audio/mpeg, details/preview page opened;
no browser console errors. No production deployment or user media was involved.

`git diff --check` is clean for first-party changes. The untouched upstream payload
contains whitespace flagged by Git; it is retained byte-for-byte so its recorded
checksums continue to match the official release.
