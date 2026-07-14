<?php
/*--------------------- /strings/classes/strvalidate_class_inc.php ---------------------
 * @copyright 2004, University of the Western Cape & AVOIR Project
 * @license GNU GPL
 * Author Jonathan Abrahams
 ------------------------------------------------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

/**
* This class provides some methods for validating strings.
*
* @author Jonathan Abrahams
*/
class strvalidate extends ChisimbaObject
{
    /**
    * Method to initialise the object.
    */
    function init() {
    }

    /**
    * Method to validate if a string contains alphanumeric characters
    *
    * Any Letter, upper or lower case, and
    * Digits(0-9), one to many characters.
    */
    function isAlphaNumeric( $string ) {
        return preg_match("~^[[:alpha:][:digit:][:space:]]+$~", $string );
    }

    /**
    * Method to validate if a string contains alpha characters
    *
    * Any Letter, upper or lower case, one to many characters.
    */
    function isAlpha( $string ) {
        return preg_match("~^[[:alpha:][:space:]]+$~", $string );
    }

    /**
    * Method to validate if a string contains numeric characters
    *
    * Any Digits(0-9), one to many charaters.
    */
    function isNumeric( $string ) {
        return preg_match("~^[[:digit:][:space:]]+$~", $string );
    }
} // end class
?>