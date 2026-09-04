<?php
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run'])
{
    die("You cannot view this page directly");
}
// end security check

/**
* The class that shows the skin chooser block
*
* @author Tohir Solomons
*
*/
class block_skinchooser extends ChisimbaObject
{
    /**
    * string $title Title of the Block
    */
    public $title;
    
    /**
    * Constructor for the class
    */
    function init()
    {
         //Create an instance of the language object
        $this->objLanguage = $this->getObject('language','language');
        $this->objUser = $this->getObject('user','security');
        //Set the title
        $this->title=$this->objLanguage->languageText('mod_skin_name', 'skin');
    }
    
    /**
    * Method to output a block with information on how help works
    */
    function show()
    {
        if (!$this->objUser->isAdmin()) return '';
        $objSkin = $this->getObject('skinchooser', 'skin');
        return $objSkin->show();
    }
}
?>
