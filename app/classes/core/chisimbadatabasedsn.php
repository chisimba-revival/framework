<?php
/** Shared legacy DSN parser for runtime and installer. @author Derek Keats */
final class ChisimbaDatabaseDsn
{
    public static function parse($dsn) {
        //$parsed = NULL;
        $parsed = array();
        //$arr = NULL;
        if (is_array ( $dsn )) {
            //$dsn = array_merge ( $parsed, $dsn );
            return $dsn;
        }

        // Find the 'phptype(dbsyntax)'
        if (($pos = strpos ( $dsn, '://' )) !== false) {
            $str = substr ( $dsn, 0, $pos );
            $dsn = substr ( $dsn, $pos + 3 );
        } else {
            //return array();
            $str = $dsn;
            $dsn = '';
        }
        // Split 'phptype'/'dbsyntax'
        if (preg_match ( '|^(.+?)\((.*?)\)$|', $str, $arr )) {
            $parsed ['phptype'] = rawurldecode($arr[1]);
            if (!empty($arr[2])) {
                $parsed ['dbsyntax'] = rawurldecode($arr[2]);
            }
        } else {
            $parsed ['phptype'] = rawurldecode($str);
        }
        if ($dsn == '') {
            return $parsed;
        }

        // Find the 'username:password'
        if (($pos = strrpos ( $dsn, '@' )) !== false) {
            $str = substr ( $dsn, 0, $pos );
            $dsn = substr ( $dsn, $pos + 1 );
            if (($pos_inner = strpos ( $str, ':' )) !== false) {
                $str_username = substr ( $str, 0, $pos_inner );
                $str_password = substr ( $str, $pos_inner + 1 );
                $parsed ['username'] = rawurldecode ( $str_username );
                $parsed ['password'] = rawurldecode ( $str_password );
            } else {
                $parsed ['username'] = rawurldecode ( $str );
            }
        }

        // Find the 'hostspec'('hostname[:port]')
        if (($pos = strrpos ( $dsn, '/' )) !== false) {
            $str_hostspec = substr ( $dsn, 0, $pos );
            $str_database = substr ( $dsn, $pos + 1 );
            $parsed ['hostspec'] = rawurldecode ( $str_hostspec );
            $parsed ['database'] = rawurldecode($str_database);
        } else {
            $parsed ['hostspec'] = rawurldecode($dsn);
        }
        $parsed ['hostspec'] = str_replace ( "+", "/", $parsed ['hostspec'] );
        //trigger_error($parsed ['hostspec']);

        return $parsed;
    }

}
