# Local grass guide enrichment — 21 November 2026

Deployed only to the authorised development site https://chisimba.test:8445/ch/.
No production deployment was performed.

- Framework common revision: `68e482ac8dabc8114f48a5e930d59bbf958e77fe` (utilities 2.027).
- Modules common revision: `bb83cb3347d93c2265affc8fca11ba881cfa2a70` (grassguide 0.007).
- Both revisions were published on `release/consolidation-20260930` before assembly.
- Manifest SHA-256: `af3a42adcf44ed28d0fee27583d322fc0a69a484ecfcfc1a9be107b0c8a49e65`.
- Source verification passed for all 13,677 files in both web and worker containers: 13,647 shared files and 30 unchanged, inventoried private local Applications files. Both source mounts are read-only.

The persistent `chisimba-grassguide-worker` processes independently paced provider
queues, honours Retry-After and retry cooldowns, recovers interrupted jobs, and
queues missing or expired checks. Results are cached for 30 days. Manual field
corrections, including deliberately cleared fields, take priority over imports.
API calls do not run in page requests. The development worker definition was
published in dev-environment revision `37d367b`.

Sources are Kew, GBIF, Wikipedia, iNaturalist and the pinned GrassBase dataset
https://zenodo.org/records/22302582 (DOI 10.5281/zenodo.22302582, CC-BY-4.0).
The regional GrassBase cache has a reproducible importer and source checksums;
refreshing jobs does not download a newer GrassBase release automatically.
Photo references retain creator, licence and source links. No media binaries
were republished. Unknown traits and ambiguous taxon matches remain unknown.

Final coverage across 179 species: 166 descriptions, 165 identification texts,
165 inflorescence mappings, 148 fertile-lemma awn states, 15 habitat texts,
150 Kew identifiers, and 648 credited photographs covering 149 species.
All 895 provider checks completed as fetched or unmatched, with no outstanding
errors. Thirteen species still have no sourced description. Manual review is
optional; a public mobile release remains separate work.

Validation included isolated PDO/database regression tests for imports, access,
CSRF, transactions, rollback, concurrency, licence handling, provenance, manual
overrides, provider cooldowns, job recovery and GrassBase mapping. Transport URL
policy tests, scoped PHP lint and diff checks passed. A real installed clone
rehearsed the normal catalogue update twice, exercised worker startup and all
179 GrassBase jobs, and preserved 212 protected tables. The live catalogue update
also preserved those protected tables. Browser checks covered queueing, Help,
credited image preview, narrow editor layout and final installed coverage.

Local operator evidence, backups, manifest, rehearsal/deployment results and
coverage are retained under `work/grassguide-enriched-20261121/`; sensitive
backups are not committed. Earlier enrichment rollout evidence remains in its
original work directories. Unrelated development changes were preserved.
