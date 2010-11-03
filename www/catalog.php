<?php
require_once 'classes/catalog.class.php';

$catalog = new catalog();

$data = array();
catalog_data_variables::get_vars($data);
$catalog->set_vars($data);
$catalog->fetch();
?>