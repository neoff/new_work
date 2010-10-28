<?php

require_once ('admin/classes/abstract/admin.abstract.class.php');

class tree_relations extends admin_abstract {
	
	
	function __construct() {
		parent::__construct();
	}
	
	function get_dirs($current_dir=0){
		$data = array();
		$this->db->query("SELECT distinct(kis_dirs.dir_id),kis_dirs.dir_name, (CASE WHEN kis_dirs.dir_id = '$current_dir' THEN 1 ELSE 0 END) as is_current FROM kis_dirs LEFT JOIN kis_catalog_index ON kis_dirs.dir_id = kis_catalog_index.dir_id;");
		$data['items'] = $this->db->fetch_all_result();
		return $data; 
	}
	
	function get_classes($dir_id){
		$data = array();
		$this->db->query(sprintf("SELECT distinct(kis_classes.class_id),kis_classes.class_name FROM kis_classes LEFT JOIN kis_catalog_index ON kis_classes.class_id = kis_catalog_index.class_id WHERE kis_catalog_index.dir_id = %d",$dir_id));
		$data['items'] = $this->db->fetch_all_result();
		return $data;
	}
	
	public function add_relation($data,$show_main = true){
		if(!isset($data['dir']))$data['dir'] = 0;
		if(!isset($data['class']))$data['class'] = 0;
		if(!isset($data['goods_id']))$data['goods_id'] = 0;
		if($data['dir']!=0&&$data['class']==0){
			$query_select_classes_by_dir  = "SELECT distinct(class_id) FROM kis_catalog_index WHERE dir_id = %d;";
			$this->db->query(sprintf($query_select_classes_by_dir,$data['dir']));
			$classes = $this->db->fetch_all_result();

			foreach($classes as $cid){
				$this->add_relation(array("node_id"=>$data['node_id'],"dir"=>$data['dir'],"class"=>$cid['class_id']),false);
			}
			$this->fetch($data);
			return;
		}
		
		
		$query_check = "SELECT relation_id FROM catalog_tree_relations WHERE dir_id='%d' AND class_id = '%d' AND goods_id = '%d' AND tree_id = '%d'";
		$query_add   = "INSERT INTO catalog_tree_relations (relation_id,dir_id,class_id,goods_id,tree_id) VALUES ('%d','%d','%d','%d','%d');";
		$query_select_new_relation_id = "SELECT (MAX(relation_id)+1) as new_id FROM catalog_tree_relations;";
		
		
		$this->db->query(sprintf($query_check,$data['dir'],$data['class'],$data['goods_id'],$data['node_id']));
		if($this->db->num_rows()){
			$data['final'] = 0;
			$data['message'] = 'Такая привязка уже существует';
			if($show_main)$this->fetch($data);
			return;
		}
		
		
		$this->db->query(sprintf($query_select_new_relation_id));
		$tmp = $this->db->fetch_all_result();
		$new_id = $tmp[0]['new_id'] ? $tmp[0]['new_id'] : 1;
		$node = $data['node_id'];
		$this->db->query(sprintf($query_add,$new_id,$data['dir'],$data['class'],$data['goods_id'],$data['node_id']));
		
		$select_goods_id_for_relation = '';
		if($data['class']!=0){
			$select_goods_id_for_relation = sprintf("SELECT catalog_id as goods_id FROM kis_catalog_index WHERE class_id = '%d' AND dir_id = '%d';",$data['class'],$data['dir']);	
		}
		else{
			$select_goods_id_for_relation = sprintf("SELECT catalog_id as goods_id FROM kis_catalog_index WHERE dir_id = '%d';",$data['dir']);
		}
		
		$this->db->query($select_goods_id_for_relation);
		$goods = $this->db->fetch_all_result();
		$query_add_goods_to_full_relations = "INSERT INTO catalog_tree_relations_full (relation_id,goods_id) VALUES (%d,%d);";
		$j = count($goods);
		//print_r($goods);
		for($i=0;$i<$j;$i++){
			$this->db->query(sprintf($query_add_goods_to_full_relations,$new_id,$goods[$i]['goods_id']));
		}
		unset($data);
		$data['node_id'] = $node;
		$data['message'] = 'Привязка успешно добавлена';
		if($show_main)$this->fetch($data);
	}
	
	public function fetch($data){
		$dirs = $this->get_dirs(isset($data['dir'])?$data['dir']:0);
		$classes = array();
		$T = new Blitz($this->template_dir."tree/tree_relations.html");
		$T->setGlobal(array("current_node"=>$data['node_id']));
		if(isset($data['message'])){
			$T->setGlobal(array("glob_message"=>$data['message']));
		}
		if(isset($data['dir'])){
			$T->setGlobal(array("current_dir"=>$data['dir']));
			$classes = $this->get_classes($data['dir']);
		}
		else{
			$T->setGlobal(array("dir_not_selected"=>true));
		}
		$current_relations = $this->get_relations($data['node_id']);
		$T->block("dirs",$dirs);
		$T->block("classes",$classes);
		$T->block("current_relations",$current_relations);
		echo $T->parse();
	}
	
	protected function get_relations($node){
		$query_select_relations = "
		SELECT 
			catalog_tree_relations.*,
			kis_classes.class_name,
			kis_dirs.dir_name,
			CASE WHEN catalog_tree_relations.class_id <> 0THEN 1 ELSE 0 END as is_class,
			CASE WHEN catalog_tree_relations.dir_id <> 0  AND catalog_tree_relations.class_id = 0  THEN 1 ELSE 0 END as is_dir
		FROM
			catalog_tree_relations
		LEFT JOIN
			kis_dirs
			ON
				kis_dirs.dir_id = catalog_tree_relations.dir_id
		LEFT JOIN
			kis_classes
			ON
				kis_classes.class_id = catalog_tree_relations.class_id
		WHERE 
			catalog_tree_relations.tree_id = %d;";
		
		$this->db->query(sprintf($query_select_relations,$node));
		$data['items'] = $this->db->fetch_all_result();
		//print_r($data);
		return $data;
	}
	
	public function delete_relations($data){
		$tmp_a = $data['relation_id'];
		$rel_ids = array_keys($tmp_a);
		foreach($rel_ids as $rid){
			$this->db->query("DELETE FROM catalog_tree_relations WHERE relation_id = $rid;
							  DELETE FROM catalog_tree_relations_full WHERE relation_id = $rid;");
		}
		$this->fetch($data);
	}
	
	
	function __destruct() {
		parent::__destruct();
	}
}

?>