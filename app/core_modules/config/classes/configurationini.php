<?php
/** Native INI codec. Legacy reads retain comma-list/boolean conventions.
 * New writes mark lossless quoted-string semantics; no environment/constant expansion.
 * GPL-2.0-or-later. Author: Derek Keats.
 */
require_once __DIR__ . '/configurationnode.php';
class ChisimbaConfigurationIni
{
    const HEADER = '; Chisimba configuration INI v1';
    public function parse($bytes, $rootName = 'root')
    {
        if (!preg_match('//u', $bytes) || str_contains($bytes, "\0")) { throw new RuntimeException('Invalid INI text.'); }
        $native=str_starts_with($bytes,self::HEADER."\n");
        $root=new ChisimbaConfigurationNode('section','root'); $section=$root;
        foreach (preg_split('/\r\n|\n|\r/', $bytes) as $line) {
            $line=trim($line);
            if ($line === '' || $line[0] === ';' || $line[0] === '#') { continue; }
            if (preg_match('/^\[([^\[\]\r\n]+)\]\s*(?:;.*)?$/D',$line,$m)) {
                $section=$root->getItem('section',$m[1]);
                if (!$section) { $section=$root->createSection($m[1]); }
                continue;
            }
            if (!preg_match('/^([^=]+?)\s*=\s*(.*)$/D',$line,$m)) { throw new RuntimeException('Invalid INI directive.'); }
            $key=trim($m[1]); $source=$m[2]; $append=str_ends_with($key,'[]');
            if ($append) { $key=substr($key,0,-2); }
            $this->name($key);
            $quoted=$source !== '' && ($source[0] === '"' || $source[0] === "'");
            if ($native) {
                try { $value=json_decode($source,true,512,JSON_THROW_ON_ERROR); }
                catch (JsonException $e) { throw new RuntimeException('Invalid quoted INI value.'); }
                if (!is_string($value)) { throw new RuntimeException('Invalid INI value.'); }
            } elseif ($quoted) {
                $quote=$source[0]; $value=''; $closed=false;
                for ($i=1,$n=strlen($source);$i<$n;$i++) {
                    $char=$source[$i];
                    if ($char === $quote) {
                        $tail=trim(substr($source,$i+1));
                        if ($tail !== '' && $tail[0] !== ';') { throw new RuntimeException('Invalid INI quote.'); }
                        $closed=true; break;
                    }
                    if ($quote === '"' && $char === '\\' && $i+1<$n && in_array($source[$i+1],array('"','\\','$'),true)) { $char=$source[++$i]; }
                    $value.=$char;
                }
                if (!$closed) { throw new RuntimeException('Unterminated INI quote.'); }
            } else {
                $value=trim(explode(';',$source,2)[0]);
                $boolean=strtolower($value);
                if (in_array($boolean,array('on','yes','true'),true)) { $value='1'; }
                if (in_array($boolean,array('off','no','false','null'),true)) { $value=''; }
            }
            if (!$append) {
                // Repeated legacy keys replace their previous values, like PHP's INI scanner.
                while ($old=$section->getItem('directive',$key)) { $old->removeItem(); }
            }
            $values=!$native && $section !== $root && !str_contains($value,'"') ? preg_split('/\s*,\s+/',$value) : array($value);
            foreach ($values as $item) { $section->createDirective($key,$item); }
        }
        return array($root,'UTF-8');
    }
    public function render($root, $rootName = 'root', $encoding = 'UTF-8')
    {
        $output=self::HEADER."\n";
        foreach ($root->children as $node) {
            if ($node->type === 'directive') { $output.=$this->directive($root,$node); }
        }
        foreach ($root->children as $node) {
            if ($node->type !== 'section') { continue; }
            $this->name($node->name); $output.='['.$node->name."]\n";
            if ($node->attributes) { throw new RuntimeException('INI attributes are not supported.'); }
            foreach ($node->children as $child) {
                if ($child->type !== 'directive') { throw new RuntimeException('Nested INI sections are not supported.'); }
                $output.=$this->directive($node,$child);
            }
        }
        $this->parse($output); return $output;
    }
    private function directive($parent,$node)
    {
        $this->name($node->name);
        if ($node->attributes || (!is_scalar($node->content) && $node->content !== null)) { throw new RuntimeException('Invalid INI value.'); }
        $value=$node->content === false ? '0' : (string)$node->content;
        if (!preg_match('//u',$value) || str_contains($value,"\0")) { throw new RuntimeException('Invalid INI text.'); }
        return $node->name.($parent->countChildren('directive',$node->name)>1 ? '[]' : '').'='
            .json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
    }
    private function name($name)
    {
        if (!is_string($name) || trim($name)!==$name || $name==='' || preg_match('/[\x00-\x1f\x7f\[\]=;#"\x27]/',$name)) {
            throw new RuntimeException('Invalid INI name.');
        }
    }
}
