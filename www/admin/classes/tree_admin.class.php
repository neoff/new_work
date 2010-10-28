<?php

require_once 'admin/classes/abstract/admin.abstract.class.php';

/**
 * Класс для администрирования дерева каталога
 *
 */

class tree_admin extends admin_abstract {
		
	function __construct() {
		parent::__construct();
	}
	
	protected function get_tree($current_node=0,$current_parent=0){
		$current_parent = $current_parent == '' ? 0 : $current_parent; 
		$query_select_child = "SELECT 
			catalog_tree.*, 
			catalog_tree_groups.tree_group_name as group_name, 
			CASE 
				WHEN catalog_tree.tree_id = %d 
					OR catalog_tree.tree_id = %d 
					THEN 1 
				ELSE 0 
			END as is_current 
			FROM 
				catalog_tree 
				LEFT JOIN 
					catalog_tree_groups 
					ON 
						catalog_tree_groups.tree_group_id = catalog_tree.tree_group 
			WHERE tree_parent = '%d' ORDER BY tree_group,tree_order;";
		$this->db->query("SELECT catalog_tree.*,CASE WHEN catalog_tree.tree_id = $current_node OR catalog_tree.tree_id = $current_parent THEN 1 ELSE 0 END as is_current FROM catalog_tree WHERE tree_parent = 0 ORDER BY tree_order;");
		$tree = $this->db->fetch_all_result();
		$j = count($tree);
		for($i=0;$i<$j;$i++){
			$this->db->query(sprintf($query_select_child,$current_node,$current_parent,$tree[$i]['tree_id']));
			$tmp = $this->db->fetch_all_result();
			$tree[$i]['not_count_subs'] = (!empty($tmp) ? 0 : 1);
			$tree[$i]['childs'] = $tmp;
			unset($tmp);
		}
		return $tree;
		
	} 

	protected function get_node($id){
		$this->db->query("SELECT * FROM catalog_tree WHERE tree_id = $id");
		$data = $this->db->fetch_all_result();
		return $data[0];
	}
	
	protected function get_groups($current_group=0){
		$this->db->query("SELECT * FROM catalog_tree_groups;");
		$groups = $this->db->fetch_all_result();
		if($current_group!=0){
			$j = count($groups);
			for($i=0;$i<$j;$i++){
				if($groups[$i]['tree_group_id']==$current_group){
					$groups[$i]['is_current'] = 1;
				}
			}
		}
		return $groups;
		
	}
	
	protected function get_parents($current_parent=0,$current_node_id=0){
		if($current_node_id!=0){
			$this->db->query("SELECT * FROM catalog_tree WHERE tree_parent = $current_node_id LIMIT 1;");
			if($this->db->fetch_all_result()){
				return false;
			}
		}
		$this->db->query("SELECT * FROM catalog_tree WHERE tree_parent = 0;");
		$parents = $this->db->fetch_all_result();
		
		if($current_parent!=0||$current_node_id!=0){
			$j = count($parents);
			for($i=0;$i<$j;$i++){
				if($parents[$i]['tree_id']==$current_parent){
					$parents[$i]['is_current'] = 1;
					break;
				}
				if($current_node_id==$parents[$i]['tree_id']){
					unset($parents[$i]);
				}
			}
		}
		return $parents;
		
		
	}

	public function show_node($data){
		$this->show_main($data);
	}
	
	
	public function show_main($data=false){
		$current_node = array();
		if($data['tree_id']){
			$current_node = $this->get_node($data['tree_id']);
			$current_node['parents'] = $this->get_parents($current_node['tree_parent'],$current_node['tree_id']);
			$current_node['groups'] =$this->get_groups($current_node['tree_group']);
		} else {
			$current_node['parents'] = $this->get_parents();
			$current_node['groups'] =$this->get_groups();
		}
		$current_tree = $this->get_tree((isset($current_node['tree_id']))?($current_node['tree_id']):(0),(isset($current_node['tree_parent']))?($current_node['tree_parent']):(0));
		$T = new Blitz($_SERVER['DOCUMENT_ROOT']."/templates/admin/tree/index.html");
		if(count($current_tree)==0){
			$T->block("current_tree",array("tree_message"=>"Tree is empty."));
		}else{
			#$T->setGlobal(array("current_node"=>$data['tree_id'],"current_parent"=>$data['tree_parent']));
			$T->block("current_tree",array("tree_nodes"=>$current_tree));
		}
		$T->block("current_node",$current_node);

		if($current_node['parents'] == false){
			$T->block("current_node/parents_message");
		}
		echo $T->parse();
		
	}

	public function save_node($data){
		if(!$data['tree_id']){
			$this->add_node($data);
			return;
		};
		if($data['new_group']){
			$data['tree_group'] = $this->add_group($data['new_group']);
		}
		echo 1;
		$query_update_node = "UPDATE catalog_tree SET tree_name='%s', tree_parent = '%d', tree_order='%d', tree_group='%d' WHERE tree_id = %d;";
		$this->db->query(sprintf($query_update_node,$data['tree_name'],$data['tree_parent'],$data['tree_order'],$data['tree_group'],$data['tree_id']));
		$this->show_main($data);
	}
	
	protected function add_group($group_name,$group_order=0){
		$this->db->query(sprintf("SELECT tree_group_id FROM catalog_tree_groups WHERE UPPER(tree_group_name) = '%s';",strtoupper($group_name)));
		$tmp = $this->db->fetch_all_result();
		if($tmp){
			return $tmp[0]['tree_group_id'];
		}
		$this->db->query("SELECT MAX(tree_group_id) + 1 as max_id FROM catalog_tree_groups;");
		$tmp = $this->db->fetch_all_result();
		$new_id = ($tmp==false || $tmp[0]['max_id'] == 0) ? 1 :$tmp[0]['max_id'];
		$this->db->query(sprintf("INSERT INTO catalog_tree_groups (tree_group_id,tree_group_name,tree_group_order) VALUES ('%d','%s','%d');",$new_id,$group_name,$group_order));
		return $new_id;
	}
	
	protected function add_node($data){
		if($data['new_group']){
			$data['tree_group'] = $this->add_group($data['new_group']);
		}
		$this->db->query(sprintf("SELECT MAX(tree_id) + 1 as max_id FROM catalog_tree;"));
		$tmp = $this->db->fetch_all_result();
		$new_id = ($tmp==false || $tmp[0]['max_id'] == 0) ? 1 :$tmp[0]['max_id'];
		
		$data['tree_id'] = $new_id;
		$this->db->query(sprintf("INSERT INTO catalog_tree (tree_id,tree_name,tree_parent,tree_order,tree_group,tree_block) VALUES ('%d','%s','%d','%d','%d','%d')",
										$new_id,$data['tree_name'],$data['tree_parent'],$data['tree_order'],$data['tree_group'],isset($data['tree_block'])?('true'):('false')));
	
		$this->show_main($data);
	}
	
	
	public function delete_node($data){
		if(!$data['tree_id']){
			$this->show_main();
			return;
		}
		$id = $data['tree_id'];
		$this->db->query("DELETE FROM catalog_tree WHERE tree_id = $id OR tree_parent = $id;");
		$this->show_main();
	}
	
	function __destruct() {
		parent::__destruct();
	}
}

?>