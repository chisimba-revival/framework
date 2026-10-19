# Shared legacy client cleanup — 17 October 2026

Global jQuery loading remains enabled. This checkpoint removes redundant shared
clients; it is not certification of all remaining modules.

## Changed

* Legacy showlogin delegates to Security's existing logininterface, including
  native CSRF/abuse evidence. Its dropdown uses details/summary. Identity and
  translated labels are escaped; unknown render-method names cannot dispatch
  arbitrary methods. Legacy AJAX flags no longer create another login path.
* The Login controller routes ordinary visits to Security and returns the native
  form for old fragment routes. Old CAPTCHA routes return 410. The unused backend
  nonce/CAPTCHA classes/schema remain: their deletion was blocked by automatic
  approval review and awaits separate authorisation. Existing database data is intact.
* The disabled User Registration sliding panel remains disabled via a small skin
  compatibility object. Removed its unreferenced register/slide scripts and private
  jQuery 1.3.2 copy. Normal registration remains in its existing controller/service.
* Chrome Frame loading is inert regardless of old configuration. Removed its
  loader and the unused empty skin chisimba.js shell. Removed loginsupport.js.
* Embedded Security login fields now have escaped accessible names and standard
  username/current-password autocomplete. Registered the missing recovery-link term.

Versions: Skin 0.214, User Registration 0.047, Login 0.082, Security 3.116.
Only already-registered modules were updated locally; Login remains dormant.
No production deployment or schema/data migration.

## Evidence and limits

Focused wrapper checks cover native delegation, dropdown escaping, safe dispatch,
logged-in identity, disabled panel and inert browser-plugin method. Native login
failure behavioural checks pass. The existing PRG source contract was stale: it
expected the superseded systemmanagement/home redirect. It now checks the current
Security sign-in failure destination; no production redirect logic was changed.
PHP syntax and whitespace checks pass.

An isolated local PHP 8.5/PDO clone suppressed automatic jQuery. Installed native
form rendering verified CSRF and abuse fields. Chrome exercised keyboard disclosure
using rendered fixture HTML, accessible field names, and an actual canonical sign-in
with a synthetic administrator credential. The successful page contained no jQuery,
loginsupport or Chrome Frame script URLs and no captured console errors.
The static disclosure fixture was not used to submit authentication. Fresh-install
support for the old Login module is not certified; its older schema remains outside
this checkpoint. Temporary clone/config/session data was removed after validation.

Local scan: 195 candidate stored-content columns, zero references to the removed
login/Chrome Frame/registration clients. This is not a production stored-content audit.
Local Login, Wall, SimpleGal, ePortfolio, CMS/CMS Admin, Blogcomments, Simplefeedback,
Textblock, Slate and Stripe are unregistered; that does not prove unused on other sites.

## Remaining tracing

* Multi-file selectors retain jQuery but currently have no external source callers.
* Context searchBlock_ / searchBlock__ have no source callers; their old autocomplete
  and tablesorter branches need deliberate retirement, not a blanket text replacement.
* Wall and SimpleGal have explicit script loaders; ePortfolio upload has a routed
  template. Preserve ePortfolio for its planned use; CMS revival is deferred.
* Old filters (including the separate blog AJAX filter) and Blogcomments require
  reachability and stored-use checks. The retired blogpost RPC filter is different.
* Old Textblock/Slate assets must be distinguished from their modern controllers;
  Prototype dollar calls and TinyMCE's internal code are not global jQuery consumers.

Do not switch the global loader based only on this text inventory.

## Authorised backend follow-up

Derek subsequently authorised backend retirement after separate checks. The nonce,
dbnonce, CAPTCHA and credential-filter classes and obsolete source schema are now
removed in Login 0.083. See [separate validation](retired-login-backend-20261017.md).
This supersedes the retained-backend limitation above.
