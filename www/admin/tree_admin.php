<?php
require_once 'admin/classes/tree_admin.class.php';

$ta = new tree_admin();

$action = '';
if(isset($_REQUEST['action']))$action = $_REQUEST['action'];

switch($action){
	case "":
	case "add_node":
	case "show_main":
		$ta->show_main();
	break;
	case "show_node":
		$ta->show_node($_REQUEST['data']);
	break;
	case "save_node":
		$ta->save_node($_REQUEST['data']);
	break;
	case "delete_node":
		$ta->delete_node($_REQUEST['data']);
	break;
}



?>