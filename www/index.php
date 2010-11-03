<?php

$start = microtime(true);


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