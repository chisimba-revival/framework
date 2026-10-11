<?php
/** The array/scalar XML contract used by Chisimba metadata; no object deserialisation. */
final class ChisimbaXmlValue
{
    private const MAX_BYTES = 8388608;
    private const MAX_DEPTH = 64;

    public static function read(string $xml): mixed
    {
        if (strlen($xml) > self::MAX_BYTES) throw new InvalidArgumentException('XML metadata is too large.');
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = new DOMDocument();
            $doc->resolveExternals = false;
            $doc->substituteEntities = false;
            if ($xml === '' || !$doc->loadXML($xml, LIBXML_NONET) || $doc->doctype || !$doc->documentElement) {
                throw new InvalidArgumentException('Invalid XML metadata.');
            }
            $remaining = 100000;
            return self::value($doc->documentElement, 0, $remaining);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function value(DOMElement $node, int $depth, int &$remaining): mixed
    {
        if ($depth > self::MAX_DEPTH || --$remaining < 0) throw new InvalidArgumentException('XML metadata is too complex.');
        $children = []; $counts = []; $text = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $key = $child->hasAttribute('_originalKey') ? $child->getAttribute('_originalKey') : $child->tagName;
                $value = self::value($child, $depth + 1, $remaining);
                $count = $counts[$key] ?? 0;
                if ($count === 0) $children[$key] = $value;
                elseif ($count === 1) $children[$key] = [$children[$key], $value];
                else $children[$key][] = $value;
                $counts[$key] = $count + 1;
            } elseif ($child instanceof DOMText || $child instanceof DOMCdataSection) $text .= $child->nodeValue;
        }
        $text = trim($text);
        $type = strtolower($node->getAttribute('_type'));
        if ($type === 'object' || $node->hasAttribute('_class')) throw new InvalidArgumentException('Object XML metadata is unsupported.');
        if ($children || $type === 'array') {
            if ($text !== '') $children['_content'] = $text;
            return $children;
        }
        return match ($type) {
            '', 'string', 'resource' => $text,
            'integer', 'int' => (int)$text,
            'double', 'float' => (float)$text,
            'boolean', 'bool' => (bool)$text,
            'null' => null,
            default => throw new InvalidArgumentException('Unsupported XML metadata type.'),
        };
    }

    public static function write(mixed $value): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $remaining = 100000;
        $root = self::element($doc, gettype($value), $value === false ? '' : $value, 0, $remaining);
        $doc->appendChild($root);
        $xml = $doc->saveXML($root);
        if ($xml === false || strlen($xml) > self::MAX_BYTES) throw new InvalidArgumentException('XML metadata is too large.');
        return $xml;
    }

    private static function element(DOMDocument $doc, string $name, mixed $value, int $depth, int &$remaining): DOMElement
    {
        if ($depth > self::MAX_DEPTH || --$remaining < 0) throw new InvalidArgumentException('XML metadata is too complex.');
        if (is_object($value) || is_resource($value)) throw new InvalidArgumentException('XML metadata requires arrays and scalars.');
        if (!preg_match('/^[\p{L}_][\p{L}\p{N}_.:-]*$/uD', $name)) $name = 'XML_Serializer_Tag';
        $node = $doc->createElement($name);
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $node->appendChild($doc->createTextNode("\n"));
                $node->appendChild(self::element($doc, (string)$key, $item, $depth + 1, $remaining));
            }
            if ($value) $node->appendChild($doc->createTextNode("\n"));
        } elseif ($value !== null && $value !== '') {
            $text = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
            if (!mb_check_encoding($text, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $text)) throw new InvalidArgumentException('Invalid XML metadata text.');
            $node->appendChild($doc->createTextNode($text));
        }
        return $node;
    }
}
