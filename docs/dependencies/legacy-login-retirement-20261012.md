# OpenID and Twitter login retirement — 12 October 2026

Removed both JanRain OpenID/Yadis copies (`app/lib/Auth` and the Security
`classes/Auth` tree), the bundled TwitterOAuth/EpiTwitter libraries and examples,
the Epi wrapper, and unused provider-image assets. Removed `oauthDisp`,
`doTwitterLogin`, `openIdConnect` and `openIdLogin` from the shared login renderer.
Native `renderLoginBox` is retained for its active framework/module callers.

The existing Security controller already dispatches only native authentication,
MFA, session/logout, form-token and password-recovery actions. The removed methods
had no callers outside their own legacy implementation. Historical OpenID/Twitter
callback URLs continue through the existing default native login page; they make
no provider call and cannot create an authenticated session from callback data.

Removed registration of the OpenID/Twitter enable switches and Twitter consumer
key/secret defaults. Security version is 3.114. Existing configuration rows,
provider-associated account records, user content and historical OpenID storage
are not deleted or rewritten. Facebook-specific helpers/libraries are outside
this slice; this is not a claim that all social-network integrations are removed.

The modules repository also removes unused `Zend/OpenId`, `Zend/OpenId.php` and
`Zend/Auth/Adapter/OpenId.php`; all references were confined to that implementation.
Zend registration is 0.002. Other Zend services and generic OAuth libraries remain.

## Validation and limits

- Removal guards pass for framework libraries/methods/settings and Zend adapter.
- PHP lint and source whitespace checks pass.
- 56 of 61 authentication/Security/removal test scripts pass. Five failures were
  reproduced on the pre-change HEAD in a separate baseline tree:
  - `localpasswordprovider_test.php`: obsolete V99Connection fixture lacks fetchAll.
  - `register_block_contract_test.php`: historical exact module-version assertion.
  - `mfa_challenge_contract_test.php`: memory repository lacks five current methods.
  - `auth_integration_v14_test.php`: expects retired authenticateNatively wiring.
  - `login_failure_prg_contract_test.php`: expects an older controller gate.
  These are outstanding test maintenance, not a fully green suite. No diagnostic
  suppression or weakening of production authentication was introduced.
- Fresh anonymous local HTTP requests to showlogin, openidlogin, openidloginresult
  and twitterlogin return the native password form with native_auth_begin and no
  PHP diagnostics. Browser verification preserves the existing signed-in session.
- Local PHP 8.5 assembled `lib/Auth` was removed as well as the mounted source
  copies. No production code, settings or account records were changed.

Deployment remains pending. Include both repositories, prune every removed vendor
path from the release (including directly accessible examples), and run the normal
catalogue update. Recheck source drift and normal login/MFA/recovery in a disposable
clone before production. No user table migration or provider-account purge is needed.
