<?php
$root=dirname(__DIR__);
$provider=file_get_contents($root.'/classes/nativeauth/localpasswordprovider.php');
$credentials=file_get_contents($root.'/classes/auth_database_class_inc.php');
$guard=file_get_contents($root.'/classes/nativeauth/guardedloginapplicationservice.php');
$controller=file_get_contents($root.'/controller.php');
$register=file_get_contents($root.'/register.conf');
$checks=array(
    'password checked before active state'=>strpos($provider,"\$this->passwords->verify(")
        < strpos($provider,"!\$this->users->isUserActive(\$userId)"),
    'registration reason retained'=>str_contains($provider,"'pending_verification'"),
    'failure status crosses credential boundary'=>str_contains($credentials,'getCredentialFailureStatus()')
        && str_contains($guard,"\$failureStatus==='pending_verification'"),
    'controller presents verification guidance'=>str_contains($controller,"'mod_security_pendingverification'")
        && str_contains($register,'Check your inbox and spam folder'),
);
foreach($checks as $name=>$passed){if(!$passed){fwrite(STDERR,"FAIL: {$name}\n");exit(1);}}
echo "PASS: pending registration login has a specific verified-password journey.\n";
?>
