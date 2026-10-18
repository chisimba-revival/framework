<?php
require __DIR__.'/../classes/submissioncontentpolicy_class_inc.php';
function verify($ok){if(!$ok)throw new RuntimeException('Content policy regression');}
verify(SubmissionContentPolicy::reviewReason('AbCdEfGhIjKlMnOp','Enquiry','Hello there')==='generated_name');
foreach(['Thandolwethu','Nkosinothando','María del Carmen','李晓明','McAllister','Anne-Marie'] as $name)verify(SubmissionContentPolicy::reviewReason($name,'Wholesale books','Please quote for ten books.')==='');
verify(SubmissionContentPolicy::reviewReason('Reader','References','https://a.test https://b.test https://c.test')==='many_links');
verify(SubmissionContentPolicy::reviewReason('Reader','My site','https://example.test')==='');
verify(SubmissionContentPolicy::reviewReason('Support','Wallet verification','Please send your seed phrase.')==='credential_request');
verify(SubmissionContentPolicy::reviewReason('Author','Book about safety','A chapter explains what a private key is.')==='');
echo "PASS: conservative content review and legitimate international names\n";
