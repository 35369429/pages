<?php
if( !defined('_TDM_APPROOT') ) define( '_TDM_APPROOT' , getenv("TDM_APPROOT") );
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );
$autoload = realpath(getenv('TROOT') . "/_lp/autoload.php");
require_once($autoload);