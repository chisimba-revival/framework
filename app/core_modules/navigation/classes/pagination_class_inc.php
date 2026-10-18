<?php


// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']){
    die("You cannot view this page directly");
}

/**
* Class for building an ajax pagination item
*
* This class builds an interface to an ajax pagination system. Developers need
* to call this class and then simply build the results in an ajax function of
* their modules controller
*
* Results are taken from:
* index.php?module=<yourmodule>&action=<youraction>&page={ajaxdriven}
*
* @author Tohir Solomons
* @copyright (c)2008 UWC
* @package navigation
* @verson 0.1
*/

class pagination extends ChisimbaObject
{
    /**
    *
    * @var string $id: the ID tag from the CSS
    * @access public
    */
    public $id;
    
    /**
    *
    * @var string $module: Module results should be returned from
    * @access public
    */
    public $module;
    
    /**
    *
    * @var string $action: Action in module to use to generate results
    * @access public
    */
    public $action;
    
    /**
    *
    * @var int $numPages: Number of Pagination links
    * @access public
    */
    public $numPageLinks;
    
    /**
    *
    * @var array $extra: Array of any additional parameters
    * @access public
    */
    public $extra;

    /**
    *
    * @var int $currentPage: The number of the initial page to display
    * @access public
    */
    public $currentPage;

    /**
    * Method to construct the class.
    */
    public function init()
    {
        $this->id = 'pagenavigation';
        $this->module = 'navigation';
        $this->action = 'testpage';
        $this->numPageLinks = 20;
        $this->extra = array();
        $this->currentPage = 0;
    }

    /**
    * Method to show the pagination
    *
    * @param null
    * @access publc
    * @return string
    */
    public function show()
    {
        $this->appendArrayVar('headerParams', $this->getJavaScriptFile('pagination.js', 'navigation'));
        $escape = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $language = $this->getObject('language', 'language');
        $text = fn($key) => $language->languageText('mod_navigation_pagination_' . $key, 'navigation');
        $count = max(1, (int)$this->numPageLinks);
        $page = max(0, min($count - 1, (int)$this->currentPage));
        $params = is_array($this->extra) ? $this->extra : [];
        $params['action'] = $this->action;
        $params['page'] = $page;
        $url = html_entity_decode($this->uri($params, $this->module), ENT_QUOTES, 'UTF-8');
        $id = 'paginationresults_' . $this->id;
        $html = '<section data-pagination data-pages="' . $count . '" data-page="' . $page
            . '" data-url="' . $escape($url) . '" data-loading="' . $escape($text('loading'))
            . '" data-error="' . $escape($text('error')) . '">';
        $html .= '<div id="' . $escape($id) . '" data-pagination-results></div>';
        $html .= '<p data-pagination-status role="status" aria-live="polite"></p>';
        $html .= '<nav class="chisimba-cluster" data-pagination-links aria-label="' . $escape($text('label')) . '">';
        // A bounded set of ordinary links remains usable without JavaScript.
        $pages = array_unique(array_merge([0, $count - 1], range(max(0, $page - 4), min($count - 1, $page + 4))));
        sort($pages);
        foreach ($pages as $number) {
            $params['page'] = $number;
            $html .= '<a class="button" href="' . $escape(html_entity_decode($this->uri($params, $this->module), ENT_QUOTES, 'UTF-8'))
                . '" data-page="' . $number . '" aria-controls="' . $escape($id) . '"'
                . ($number === $page ? ' aria-current="page"' : '') . '>' . ($number + 1) . '</a>';
        }
        return $html . '</nav></section>';
    }
}
