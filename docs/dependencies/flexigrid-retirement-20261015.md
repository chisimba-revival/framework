# Older Flexigrid retirement — 15 October 2026

The jquerycore Flexigrid package was already retired. This change removes the
separate jquery/resources/plugins/flexigrid-1.1 package (21 files, including
scripts, styles and images) and loadFlexgridPlugin(), which only terminated with
a deprecation message. No active source caller was found across both repositories.
The remaining jqGrid comment comparing grid products is not a dependency.

CMS Admin's apparent callers were PHP comments. Removed only T_COMMENT tokens
containing Flexigrid from its example template. Comparing significant PHP tokens
before/after confirms all executable tokens and inline HTML are identical. No
CMS workflow, schema or data changes. jqGrid remains a separate dependency.

Checks: both changed PHP files lint successfully; shared loader/stale-session
regression passes with warnings treated as failures; Git whitespace checks pass.
Local browser acceptance remains pending from the preceding round: containers
were stopped and browser automation could not load its request-header policy.
No deployment. Release matching framework/modules together after local smoke.

Next: trace jqGrid/Boxy and the remaining CMS/Forms menu/dialog callers, including
permissions and write endpoints. This retirement does not remove jQuery or UI.

Read-only six-site scan: zero stored flexigrid/flexgrid matches across
125/174/124/244/237/237 relevant columns. Counts-only receipts and scanner are in
work/flexigrid-retirement-20261015. This excludes encoded names, arbitrary column
types and external clients; it is supporting evidence, not universal proof.
