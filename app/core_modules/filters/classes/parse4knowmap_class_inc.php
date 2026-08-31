<?php
/**
 * Content filter for read-only Active Knowledge Map embeds.
 *
 * Recognises the compact form [knowmap id=some_id_value] and its quoted
 * equivalent. Authorization remains the responsibility of the owning module.
 *
 * @author Derek Keats
 * @package filters
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');

/** Delegates knowledge-map rendering to the module-owned embed service. */
class parse4knowmap extends ChisimbaObject
{
    /** Initialise the module-owned renderer. */
    public function init(){$this->renderer=$this->getObject('knowmapembedservice','knowledgemap');}

    /** Replace one validated knowmap token with an authorized read-only view. */
    public function parse($text){return preg_replace_callback('/\[knowmap\s+id\s*=\s*(?:"([a-f0-9]{32})"|([a-f0-9]{32}))\s*\]/i',function($match){return $this->renderer->render($match[1]!==''?$match[1]:$match[2]);},(string)$text);}
}
?>
