<?php
/** Native configuration tree: only the mutable document operations used by Chisimba.
 * GPL-2.0-or-later. Author: Derek Keats.
 * No parsing, global registry or file-writing API is exposed on a node.
 */
class ChisimbaConfigurationNode
{
    public $type;
    public $name;
    public $content;
    public $attributes;
    public $children = array();
    public $parent;

    public function __construct($type = 'section', $name = 'root', $content = '', $attributes = null)
    {
        $this->type=$type; $this->name=$name; $this->content=$content; $this->attributes=$attributes;
    }
    public function &createSection($name, $attributes = null)
    {
        $node = new self('section', $name, null, $attributes);
        $node->parent=$this; $this->children[]=$node; return $node;
    }
    public function &createDirective($name, $content, $attributes = null)
    {
        $node = new self('directive', $name, $content, $attributes);
        $node->parent=$this; $this->children[]=$node; return $node;
    }
    /** Last matching child is the historical default; explicit index is supported. */
    public function &getItem($type = null, $name = null, $content = null, $attributes = null, $index = -1)
    {
        $matches=array();
        foreach ($this->children as $node) {
            if ($type !== null && $node->type !== $type) { continue; }
            if ($name !== null && $node->name !== $name) { continue; }
            if ($content !== null && $node->content !== $content) { continue; }
            if ($attributes !== null && array_diff_assoc($attributes, $node->attributes ?: array())) { continue; }
            $matches[]=$node;
        }
        $result=$matches[$index < 0 ? count($matches)-1 : $index] ?? false;
        return $result;
    }
    public function getContent() { return $this->content; }
    public function setContent($content) { $this->content=$content; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getAttributes() { return $this->attributes; }
    public function getChild($index = 0) { return $this->children[$index] ?? false; }
    public function countChildren($type = null, $name = null)
    {
        return count(array_filter($this->children, static function ($node) use ($type,$name) {
            return ($type === null || $type === $node->type) && ($name === null || $name === $node->name);
        }));
    }
    public function removeItem()
    {
        if ($this->parent === null) { return false; }
        $parent=$this->parent;
        $parent->children=array_values(array_filter($parent->children, function ($node) { return $node !== $this; }));
        $this->parent=null; return true;
    }
    public function toArray($useAttr = true)
    {
        if ($this->type === 'directive') {
            return array($this->name => $useAttr && $this->attributes
                ? array('#'=>$this->content, '@'=>$this->attributes) : $this->content);
        }
        $values=$useAttr && $this->attributes ? array('@'=>$this->attributes) : array();
        $counts=array();
        foreach ($this->children as $child) {
            $value=$child->toArray($useAttr)[$child->name]; $key=$child->name;
            if (!isset($counts[$key])) { $values[$key]=$value; $counts[$key]=1; }
            else {
                if ($counts[$key] === 1) { $values[$key]=array($values[$key]); }
                $values[$key][]=$value; ++$counts[$key];
            }
        }
        return array($this->name=>$values);
    }
    /** Convert the documented @/#/duplicate-array shape without executing PHP. */
    public static function fromArray($values, $name = 'root')
    {
        if (!is_array($values)) { throw new RuntimeException('Configuration values must be an array.'); }
        $root=new self('section',$name);
        self::populate($root,$values); return $root;
    }
    private static function populate($parent,$values)
    {
        foreach ($values as $key=>$value) {
            if ($key === '@') {
                if (!is_array($value)) { throw new RuntimeException('Invalid configuration attributes.'); }
                $parent->attributes=$value; continue;
            }
            if ($key === '#') {
                if (count(array_diff(array_keys($values),array('@','#')))) { throw new RuntimeException('Invalid mixed configuration value.'); }
                $parent->type='directive'; $parent->content=$value; continue;
            }
            $items=is_array($value) && $value !== array() && array_is_list($value) ? $value : array($value);
            foreach ($items as $item) {
                if (is_array($item)) { $child=$parent->createSection((string)$key); self::populate($child,$item); }
                else { $parent->createDirective((string)$key,$item); }
            }
        }
    }
}
