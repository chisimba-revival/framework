<?php
// Exercise the actual template expressions using the language API argument contract.
if(PHP_SAPI!=='cli')exit(64);
$root=dirname(__DIR__);
$source=file_get_contents($root.'/templates/content/step1.php');
$register=file_get_contents($root.'/register.conf');
$language=new class($register) {
    private $register;
    public function __construct($register){$this->register=$register;}
    public function languageText($key,$module,$fallback=null){
        if($module!=='contextadmin')throw new RuntimeException('Incorrect module for '.$key);
        if(!preg_match('/^TEXT: '.preg_quote($key,'/').'\|[^|]*\|(.+)$/m',$this->register,$match))throw new RuntimeException('Missing registration '.$key);
        if($fallback!==$match[1])throw new RuntimeException('Fallback differs from registered text');
        return $match[1];
    }
};
foreach(['microlearning','masterclass'] as $format){
    $key='mod_contextadmin_format_'.$format.'_help';
    if(!preg_match('/\$this->objLanguage->languageText\(\s*\x27'.$key.'\x27.*?\)/s',$source,$match))throw new RuntimeException('Missing template expression');
    $expression=str_replace('$this->objLanguage','$language',$match[0]);
    $text=eval('return '.$expression.';');
    if($text===''||str_contains($text,'Language item not found'))throw new RuntimeException('Bad help text');
    echo 'PASS: '.$format.' uses its registered contextadmin help text'.PHP_EOL;
}
