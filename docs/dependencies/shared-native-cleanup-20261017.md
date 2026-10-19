# Shared native controls and Facebox retirement — 17 October 2026

## Completed batch

The native notice and alert-link changes in the two preceding reports are part
of this batch. Facebox is now retired, superseding their earlier statements that
Poll and Canvas retain its loader. Poll is preserved as poll__PAUSED at Derek's
request; its automatic course-home popup is removed. Canvas's dead private
loader and Facebox link markers are removed. Modern canvas selection remains
on its existing protected POST path. ePortfolio is preserved: both import-status
layouts now share persistent escaped notices instead of Facebox/Ajax calls.

Removed 12 Facebox assets (both JavaScript copies, upstream README, two CSS files,
and seven skin images), their CSS imports and generated/style-cache entries.
The old alertbox::putJs entry point is a no-op for compatibility. Legacy facebox
query parameters that select chrome-free page layouts remain; they do not load
the library. No active source caller invokes Facebox. Stored-text checks on local
and all six production sites found only one translated help message per site;
its source is updated to describe ordinary links/native fragment previews.

File Manager's single-file Clear control and legacy restricted-file visibility
toggles use native DOM APIs. Clearing targets only the named fields and emits
change events. Legacy layout adjustment is optional. The multi-file selectors,
quota pagination and other callers remain separate work.

Assignment's group/outcome disclosures and the quiz question-format/option-count
controls now use native scripts. Hiding preserves selections and drafts. True/
false submits two options without enabling unrelated disabled inputs. No grading,
permissions or persistence policy was changed by these client migrations.

## PHP 8 repairs discovered during installed checks

Declared observed state on Canvas, Assignment, quiz, date-picker and supporting
objects. Removed the reference assignments/call that emitted warnings on the
exercised paths. The quiz form now constructs htmltable without passing a string
as the framework engine; its ID is assigned explicitly. Null optional question
filters are normalised before string operations. Wizard step arithmetic explicitly
converts the legacy `2a`/`3a` strings to their numeric step. These are source fixes,
not additional warning suppression.

The old image-picker static test expected a literal getParam('imageselect') call.
The field now comes through the secured draft loop. Its assertion was updated;
the independent course-information behavioural save test also passes.

## Validation and limits

* PHP lint and diff whitespace checks; native notice, alert-link, file selector,
  course-information save and canvas chooser tests pass.
* MCQ question-action and overview-management regressions pass.
* ePortfolio notice tests cover both messages, escaping, arrays/unknown values.
* Isolated local database clone, read-only source mounts, no outbound network or
  workers, automatic jQuery loading suppressed: sign-in, actual canvas selection
  and save, File Manager selection/clear/list controls, actual Assignment form
  disclosure, creation of a disposable quiz and actual question-type/true-false
  controls pass. Script inspection found no jQuery/Facebox assets on sampled pages.
* Rendered picker-script browser fixture checks Clear and Show/Hide. Learning
  fixture checks retained selections/drafts and actual form serialization of two
  options with an unrelated protected control still excluded.
* Repeated actual Assignment/quiz/canvas pages after repairs: no browser errors
  and an empty PHP log. This is scoped evidence, not certification of every module.
* Normal catalogue updates for touched registered modules passed on the clone.

Evidence is under workspace work/native-picker-20261017 and
work/facebox-retirement-20261017. No production deployment. SCORM/old FAQ/Poll source
pauses are included in the coordinated modules checkpoint; user-owned unrelated
pauses remain outside the commits. ePortfolio is not enabled locally.

## Why automatic jQuery loading remains

The cleanup is not finished. Discussion has substantial client code and settings
writes that need security review. Other shared callers include Stories summaries,
external block loading, tagging suggestions and old quota pagination/multi-file
selectors. Quizzes still have separate numerical/question-view scripts. Preserved
CMS, ePortfolio and Formbuilder also retain dependencies. Text inventory includes
comments and dormant branches; it is not proof of live use or retirement authority.

Next: trace the active Discussion paths and their mutation permissions first;
then the remaining shared services and quiz scripts. Keep CMS for its separate
revival and ePortfolio for possible EcoTraining use. Global loading must stay until
those consumers are migrated or explicitly load a reviewed compatibility runtime.
