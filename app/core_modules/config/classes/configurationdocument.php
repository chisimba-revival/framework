<?php
/**
 * Independent configuration state, destination, encoding and revision.
 * GPL-2.0-or-later. Author: Derek Keats.
 */
require_once __DIR__ . '/configurationfile.php';
require_once __DIR__ . '/configurationxml.php';
require_once __DIR__ . '/configurationini.php';
class ChisimbaConfigurationDocument
{
    public $root;
    private $path;
    private $rootName;
    private $bytes;
    private $encoding = 'ISO-8859-1';
    private $files;
    private $codec;

    public function __construct($path, $rootName, $allowMissing = false, $files = null, $format = 'XML')
    {
        $this->path = $path;
        $this->rootName = $rootName;
        $this->files = $files ?: new ChisimbaConfigurationFile();
        if (!in_array($format, array('XML','INI'), true)) { throw new RuntimeException('Unsupported configuration format.'); }
        $this->codec = $format === 'INI' ? new ChisimbaConfigurationIni() : new ChisimbaConfigurationXml();
        $this->bytes = $this->files->read($path);
        if ($this->bytes === null) {
            if (!$allowMissing) { throw new RuntimeException('Configuration file is missing.'); }
            $this->root = new ChisimbaConfigurationNode('section', 'root');
            if ($format !== 'INI') { $this->root->createSection($rootName); }
        } else {
            list($this->root, $this->encoding) = $this->codec->parse($this->bytes, $rootName);
        }
    }

    /** Opaque revision for an editor spanning separate HTTP requests. */
    public function revision() { return hash('sha256', $this->bytes ?? ''); }

    /** Deep copy preserves duplicates/attributes without sharing mutable state. */
    public function copy()
    {
        return $this->codec->parse($this->codec->render($this->root, $this->rootName, $this->encoding), $this->rootName)[0];
    }

    public function save($candidate)
    {
        $bytes = $this->codec->render($candidate, $this->rootName, $this->encoding);
        $root = $this->codec->parse($bytes, $this->rootName)[0];
        $this->files->replace($this->path, $bytes, $this->bytes);
        $this->bytes = $bytes;
        $this->root = $root;
        return true;
    }

    /** Native conversion; no executable PHP configuration files are supported. */
    public function fromArray($values)
    {
        return ChisimbaConfigurationNode::fromArray($this->codec instanceof ChisimbaConfigurationIni ? $values : array($this->rootName => $values));
    }
}
