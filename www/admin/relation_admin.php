<?php
require_once 'admin/classes/tree_relations.class.php';

$rel_a = new tree_relations();

$action = (isset($_REQUEST['action']))?($_REQUEST['action']):("");
$data = $_REQUEST['data'];
switch ($action){
	case "":
		$rel_a->fetch($data);
	break;
	case "delete_relations":
		$rel_a->delete_relations($data);
	break;
	case "add_relation":
		if($data['final']){
			$rel_a->add_relation($data);
		}else $rel_a->fetch($data);
	break;
};

?>