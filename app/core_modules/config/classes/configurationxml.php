<?php
/**
 * Native XML boundary for site/properties configuration, with UTF-8 in memory.
 * The native tree preserves the documented Chisimba mutable document contract.
 * GPL-2.0-or-later. Author: Derek Keats.
 */
require_once __DIR__ . '/configurationnode.php';
class ChisimbaConfigurationXml
{
    /** Parse without DTDs, external entities, namespaces or ambiguous mixed text. */
    public function parse($bytes, $rootName)
    {
        if (!is_string($bytes) || trim($bytes) === '') {
            throw new RuntimeException('Invalid configuration XML.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($bytes, LIBXML_NONET) || $document->doctype !== null ||
                $document->documentElement->tagName !== $rootName) {
                throw new RuntimeException('Invalid configuration XML.');
            }
            $encoding = strtoupper($document->encoding ?: 'UTF-8');
            if (!in_array($encoding, array('UTF-8', 'ISO-8859-1'), true)) {
                throw new RuntimeException('Unsupported configuration encoding.');
            }
            $root = new ChisimbaConfigurationNode('section', 'root');
            $this->readElement($document->documentElement, $root, true);
            return array($root, $encoding);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function readElement($element, $parent, $forceSection = false)
    {
        if ($element->namespaceURI !== null && $element->namespaceURI !== '') {
            throw new RuntimeException('Configuration namespaces are not supported.');
        }
        $attributes = array();
        foreach ($element->attributes as $attribute) {
            if ($attribute->namespaceURI && !($forceSection && $element->tagName === 'settings'
                && $attribute->namespaceURI === 'http://www.w3.org/2001/XMLSchema-instance'
                && $attribute->localName === 'noNamespaceSchemaLocation')) {
                throw new RuntimeException('Invalid configuration attribute.');
            }
            $attributes[$attribute->namespaceURI ? 'xsi:noNamespaceSchemaLocation' : $attribute->name] = $attribute->value;
        }
        $elements = array();
        $text = '';
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) { $elements[] = $child; }
            elseif ($child instanceof DOMText || $child instanceof DOMCdataSection) { $text .= $child->nodeValue; }
            elseif ($child instanceof DOMEntityReference) { throw new RuntimeException('Invalid configuration entity.'); }
        }
        if ($elements || $forceSection) {
            if (trim($text) !== '') { throw new RuntimeException('Mixed configuration content is not supported.'); }
            $item = $parent->createSection($element->tagName, $attributes ?: null);
            foreach ($elements as $child) { $this->readElement($child, $item); }
        } else {
            $parent->createDirective($element->tagName, trim($text), $attributes ?: null);
        }
    }

    /** Render and validate before any destination is touched. */
    public function render($root, $rootName, $encoding)
    {
        $document = new DOMDocument('1.0', $encoding);
        $document->formatOutput = true;
        if (count($root->children) !== 1 || $root->children[0]->name !== $rootName) {
            throw new RuntimeException('Invalid configuration root.');
        }
        $this->writeElement($document, $document, $root->children[0]);
        $bytes = $document->saveXML();
        if ($bytes === false) { throw new RuntimeException('Configuration serialisation failed.'); }
        $this->parse($bytes, $rootName);
        return $bytes;
    }

    private function writeElement($document, $parent, $item)
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $item->name)) {
            throw new RuntimeException('Invalid configuration name.');
        }
        $element = $document->createElement($item->name);
        $parent->appendChild($element);
        foreach ($item->attributes ?: array() as $name => $value) {
            if ($name === 'xsi:noNamespaceSchemaLocation' && $item->name === 'settings' && $parent instanceof DOMDocument) {
                $element->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', $name, $this->text($value));
                continue;
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $name)) { throw new RuntimeException('Invalid configuration attribute.'); }
            $element->setAttribute($name, $this->text($value));
        }
        if ($item->type === 'section') {
            foreach ($item->children as $child) { $this->writeElement($document, $element, $child); }
        } elseif ($item->type === 'directive') {
            $element->appendChild($document->createTextNode($this->text($item->content)));
        } else {
            throw new RuntimeException('Unsupported configuration node.');
        }
    }

    private function text($value)
    {
        if (!is_scalar($value) && $value !== null) { throw new RuntimeException('Invalid configuration value.'); }
        $value = (string)$value;
        if (!preg_match('//u', $value) || preg_match('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', $value)) {
            throw new RuntimeException('Invalid configuration text.');
        }
        return $value;
    }
}
