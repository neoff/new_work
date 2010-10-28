<?php
require_once 'classes/catalog.class.php';

ini_set("display_errors",'On');
$catalog = new catalog();

$data = array();
catalog_data_variables::get_vars($data);
$catalog->set_vars($data);
$catalog->fetch();
?>