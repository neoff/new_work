<?php

$start = microtime(true);

date_default_timezone_set('Europe/Moscow');
ini_set("display_errors",'Off');
ini_set("error_reporting",E_ALL & !E_NOTICE);
session_start();
require 'classes/mainpage.class.php';

$mp = new mainpage();
$mp->fetch();

echo  sprintf("%.6f sec\n",(microtime(true) - $start) );
/*require_once '/classes/catalog.class.php';
require_once '/classes/filters.class.php';

$cat = new catalog();
$_POST[filter::$GOODS_NAME] = $_REQUEST['id'];
$cat->fetch();*/
?>