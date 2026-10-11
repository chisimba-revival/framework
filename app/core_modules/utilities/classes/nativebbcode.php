<?php
/** Bounded BBCode-to-HTML formatting. Existing HTML must be sanitised by its owner. */
final class ChisimbaBbcode
{
    private const TAGS = ['b','i','u','s','sub','sup','color','size','font','align','quote','code','url','email','img','list','ulist','li'];

    public static function parse(string $text): string
    {
        if (strlen($text) > 1048576) return $text;
        // Keep existing markup/attributes and opaque HTML elements out of BBCode parsing.
        $pattern = '~(<!--.*?-->|<(a|script|style|code|pre)\b[^>]*>.*?</\2\s*>|<(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27"<>])*?>|\[/?[a-z*]+(?:[^\]\[\r\n]*)\])~is';
        if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE) === false) return $text;
        if (count($matches[0]) > 10000) return $text;
        $root = (object)['tag'=>'','attrs'=>[],'children'=>[]];
        $stack = [$root]; $offset = 0;
        foreach ($matches[0] as [$token,$position]) {
            if ($position > $offset) end($stack)->children[] = substr($text,$offset,$position-$offset);
            $offset = $position + strlen($token);
            if (end($stack)->tag==='code' && strtolower($token)!=='[/code]') {end($stack)->children[]=$token;continue;}
            if ($token[0] === '<') {end($stack)->children[] = (object)['html'=>$token];continue;}
            if (!preg_match('~^\[(/?)([a-z*]+)(.*?)\]$~is',$token,$parts)) {end($stack)->children[]=$token;continue;}
            $tag = strtolower($parts[2]);
            if ($tag==='*') {
                if (!array_filter($stack,static fn($n)=>in_array($n->tag,['list','ulist'],true))) {end($stack)->children[]=$token;continue;}
                while (count($stack)>1 && !in_array(end($stack)->tag,['list','ulist'],true)) array_pop($stack);
                $tag='li';
            }
            if (!in_array($tag,self::TAGS,true)) {end($stack)->children[]=$token;continue;}
            if ($parts[1]==='/') {
                for($i=count($stack)-1;$i>0;$i--) if($stack[$i]->tag===$tag){$stack=array_slice($stack,0,$i);break;}
                // Unmatched closing tags do not create broken HTML.
                continue;
            }
            if(count($stack)>=64) return $text;
            $attrs=[];
            preg_match_all('~(?:^|\s)([a-z]*)=("[^"]*"|\x27[^\x27]*\x27|[^\s]+)~i',trim($parts[3]),$attributes,PREG_SET_ORDER);
            foreach($attributes as $attr) $attrs[strtolower($attr[1] ?: $tag)]=trim($attr[2],"\"'");
            $node=(object)['tag'=>$tag,'attrs'=>$attrs,'children'=>[]];
            end($stack)->children[]=$node; $stack[]=$node;
        }
        if($offset<strlen($text)) end($stack)->children[]=substr($text,$offset);
        return self::render($root);
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false);
    }

    private static function url(string $url, bool $image=false): ?string
    {
        $url=html_entity_decode(trim($url),ENT_QUOTES|ENT_HTML5,'UTF-8');
        if($url==='' || preg_match('~[\x00-\x20\x7f\\\\<>"\x27]~',$url))return null;
        if(preg_match('~^https?://~i',$url))return $url;
        if(!$image && preg_match('~^ftp://~i',$url))return $url;
        if(str_starts_with($url,'//') || preg_match('~^[^/?#]*:~',$url))return null;
        return $url;
    }

    private static function plain(object $node): string
    {
        $out='';foreach($node->children as $child) $out.=is_string($child)?$child:(isset($child->html)?$child->html:self::plain($child));
        return $out;
    }

    private static function autolink(string $text): string
    {
        $result = preg_replace_callback('~(?<![\w"\x27=])(?:(?:https?|ftp)://[^\s<>"\x27]+|www\.[^\s<>"\x27]+|[a-z0-9_.+%-]+@[a-z0-9.-]+\.[a-z]{2,})~i', static function($match) {
            $value=rtrim($match[0], '.,;!?)');$tail=substr($match[0],strlen($value));
            $url=filter_var($value,FILTER_VALIDATE_EMAIL)?'mailto:'.$value:self::url(str_starts_with($value,'www.')?'https://'.$value:$value);
            return $url===null?$match[0]:'<a href="'.self::escape($url).'">'.self::escape($value).'</a>'.$tail;
        },$text);
        return $result ?? $text;
    }

    private static function render(object $node, bool $insideLink=false): string
    {
        if(isset($node->html))return $node->html;
        $tag=$node->tag; $a=$node->attrs;
        if($tag==='code')return '<code>'.self::escape(self::plain($node)).'</code>';
        $body='';foreach($node->children as $child) $body.=is_string($child)?(($insideLink||in_array($tag,['url','email','img'],true))?$child:self::autolink($child)):self::render($child,$insideLink||in_array($tag,['url','email'],true));
        $basic=['b'=>'strong','i'=>'em','u'=>'u','s'=>'strike','sub'=>'sub','sup'=>'sup'];
        if(isset($basic[$tag]))return '<'.$basic[$tag].'>'.$body.'</'.$basic[$tag].'>';
        if($tag==='')return $body;
        if(in_array($tag,['url','email','img'],true)) {
            $value=$a[$tag]??self::plain($node);
            $url=$tag==='email'?(filter_var($value,FILTER_VALIDATE_EMAIL)?'mailto:'.$value:null):self::url($value,$tag==='img');
            if($url===null || ($insideLink && $tag!=='img'))return $body;
            if($tag==='img') {
                $dimensions='';foreach(['w'=>'width','h'=>'height'] as $key=>$attribute) if(isset($a[$key]) && ctype_digit($a[$key]) && (int)$a[$key]>0 && (int)$a[$key]<=4096) $dimensions.=' '.$attribute.'="'.(int)$a[$key].'"';
                return '<img src="'.self::escape($url).'"'.$dimensions.' />';
            }
            $target=($a['t']??'')==='_blank'?' target="_blank" rel="noopener noreferrer"':'';
            return '<a href="'.self::escape($url).'"'.$target.'>'.$body.'</a>';
        }
        if(in_array($tag,['list','ulist'],true)) {
            $ordered=$tag==='list'&&isset($a['list']);$html=$ordered?'ol':'ul';$attrs='';
            if($ordered && in_array($a['list'],['1','a','A','i','I'],true))$attrs=' type="'.$a['list'].'"';
            if($ordered&&isset($a['s'])&&preg_match('/^-?\d{1,6}$/D',$a['s']))$attrs.=' start="'.(int)$a['s'].'"';
            return '<'.$html.$attrs.'>'.$body.'</'.$html.'>';
        }
        if($tag==='li')return '<li'.(isset($a['li'])&&preg_match('/^-?\d{1,6}$/D',$a['li'])?' value="'.(int)$a['li'].'"':'').'>'.$body.'</li>';
        if($tag==='quote') {$cite=isset($a['quote'])?self::url($a['quote']):null;return '<q'.($cite!==null?' cite="'.self::escape($cite).'"':'').'>'.$body.'</q>';}
        $value=$a[$tag]??'';$style=null;
        if($tag==='color'&&preg_match('/^(?:[a-z]{1,30}|#[a-f0-9]{3}|#[a-f0-9]{6})$/iD',$value))$style='color: '.$value;
        if($tag==='size'&&is_numeric($value)&&(float)$value>=1&&(float)$value<=144)$style='font-size: '.(float)$value.'pt';
        if($tag==='font'&&preg_match('/^[a-z0-9 ,_-]{1,80}$/iD',$value))$style='font-family: '.$value;
        if($tag==='align'&&in_array($value,['left','right','center','justify'],true))$style='text-align: '.$value;
        $html=$tag==='align'?'div':'span';
        return '<'.$html.($style!==null?' style="'.self::escape($style).'"':'').'>'.$body.'</'.$html.'>';
    }
}
