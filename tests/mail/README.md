# Retained synchronous mail API

Run `php tests/mail/mime_mail_test.php` and
`python3 tests/mail/smtp_capture_test.py` on PHP 8.2+ (validated on PHP 8.5).
The latter binds a random loopback port and captures synthetic SMTP messages;
it never contacts a provider. SendGrid tests override only the HTTP request.
Strict PHP diagnostics are exceptions.

Mail 1.006 replaces PEAR Mail/Mail_mime in `mailer` with the already-pinned
PHPMailer service. Existing callers retain synchronous true/false transport
acceptance, scalar/array/comma-separated bare addresses, visible To/CC, private
BCC and configured HTML alternatives. Scalar BCC now uses BCC rather than CC.
The string `false` correctly disables HTML; an explicit `useHTMLMail` override
is supported. UTF-8 survives MIME encoding. Provider failures return false
without printing addresses, credentials or raw provider responses into pages.
No retries are introduced. A successful send is not proof of inbox delivery.

Transport configuration is shared with plainmailservice: SMTP with verified
TLS, local unauthenticated relay, mail/sendmail, or SendGrid. Insecure authenticated
SMTP is rejected. SendGrid requires a visible To recipient; BCC-only callers
must retain SMTP or be migrated deliberately, never expose BCC as To.
Attachments previously called an undefined object and never worked; `attach`
now throws an explicit exception. No first-party active attachment caller was
found. Display-name recipient syntax is not accepted: use bare email addresses.
Limits are 1,000 recipients and 1 MiB per message body part.

New notifications should use Communications. The retained facade can be removed
when its security, legacy forms/assignment/discussion/CMS callers have migrated
with their workflow-specific queue/failure semantics. This change does not
activate dormant routes or make old password-email workflows safe to revive.
A fresh PHPMailer object is created for each send; facade properties still have
the historical object lifetime, so callers must set their intended recipients.
Production deployment still requires checking each site's configured transport.
