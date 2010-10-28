<?php

require_once ('classes/abstract/module.abstract.class.php');

class catalog_tree extends module_abstract {

	private $current_node = 0;
	protected $separate_lenght = 9;
	protected $max_group = 10;
	
	function __construct() {
		parent::__construct();
	}
	

	protected function get_tree($current_node=0,$current_parent=0){
/* Может так будет быстрей, только придётся написать обработку

SELECT 
	catalog_tree.*, 
	catalog_tree_groups.tree_group_name as group_name, 
	CASE WHEN 
		catalog_tree.tree_id = 0 
		OR 
		catalog_tree.tree_id = 12 
	THEN 
		1 
	ELSE 
		0 
	END as is_current 
FROM 
	catalog_tree 
LEFT JOIN 
	catalog_tree_groups 
	ON 
		catalog_tree_groups.tree_group_id = catalog_tree.tree_group 
WHERE 
	tree_parent IN (
		SELECT catalog_tree.tree_id FROM catalog_tree WHERE tree_parent = 0 ORDER BY tree_order
	)
	OR tree_parent = 0
ORDER BY catalog_tree.tree_parent,catalog_tree_groups.tree_group_id, tree_order;
 */		
		$current_parent = $current_parent == '' ? 0 : $current_parent; 
		$query_select_child = "SELECT 
			catalog_tree.*, 
			catalog_tree_groups.tree_group_name as group_name, 
			CASE WHEN catalog_tree.tree_id = %d OR catalog_tree.tree_id = %d THEN 1 ELSE 0 END as is_current FROM catalog_tree LEFT JOIN catalog_tree_groups ON catalog_tree_groups.tree_group_id = catalog_tree.tree_group WHERE tree_parent = '%d' ORDER BY catalog_tree_groups.tree_group_id, tree_order;";
		$this->db->query("SELECT catalog_tree.*,CASE WHEN catalog_tree.tree_id = $current_node OR catalog_tree.tree_id = $current_parent THEN 1 ELSE 0 END as is_current FROM catalog_tree WHERE tree_parent = 0 ORDER BY tree_order;");
		$tree = $this->db->fetch_all_result();
		$j = count($tree);
		
		for($i=0;$i<$j;$i++){
			$this->db->query(sprintf($query_select_child,$current_node,$current_parent,$tree[$i]['tree_id']));
			$tmp = $this->db->fetch_all_result();
			$n = count($tmp);
			$last_group = $tmp[0]['tree_group'];
			$start_group = 0;
			$start_group_real = 0;
			$out_data = array();
			$i_num = 0;
			$sep_count = 0;
			$num_of_sep = 0;
			$last_sep_count = 0;
			
			
			for($k=0;$k<$n;$k++){
				if($tmp[$k]['tree_group']!=$last_group){
					
					$out_data[$i_num++] = array("is_group"=>1,"tree_name"=>$tmp[$k-1]["group_name"],"tree_group"=>$tmp[$k-1]["tree_group"]);
					for($m=$start_group;$m<=$k-1;$m++){
						$out_data[$i_num++] = $tmp[$m];
					}
					if(($sep_count+$last_sep_count >= $this->separate_lenght+2) && $start_group - 1 > 0){
						//echo "$sep_count+$last_sep_count = ".($sep_count+$last_sep_count)." \n";
						$out_data[$start_group_real]['is_end_col'] = true;
						$last_sep_count = $sep_count;
						$sep_count = 0;
						$num_of_sep++;
					}
					$last_group = $tmp[$k]['tree_group'];
					$start_group = $k;
					$start_group_real = $i_num-1;
					//echo "$start_group_real \n";
				} elseif( $k == $n-1){
					if($last_group!=0)$out_data[$i_num++] = array("is_group"=>1,"tree_name"=>$tmp[$k-1]["group_name"],"tree_group"=>$tmp[$k-1]["tree_group"]);
					for($m=$start_group;$m<=$k;$m++)$out_data[$i_num++] = $tmp[$m];
					if(($sep_count+$last_sep_count >= $this->separate_lenght+2) && $start_group - 1 > 0){
						$out_data[$start_group_real]['is_end_col'] = true;
						$sep_count = 0;
						$num_of_sep++;
					}
				}
				$sep_count++;
			}
			if($last_group == 0 && $tmp[0]['tree_group'] == 0 && $num_of_sep == 0 && count($out_data) > $this->separate_lenght){
				$n = count($out_data);
				for($k=$this->separate_lenght; $k<$n ;$k=$k+$this->separate_lenght){
					if($n-$k >( $this->separate_lenght/2) ) $out_data[$k-1]['is_end_col'] = true;
					$num_of_sep++;
				}
			}
			$tree[$i]['not_have_subs'] = (!empty($tmp) ? 0 : 1);
			$tree[$i]['width'] = ($num_of_sep+1)*200;
			$tree[$i]['childs'] = $out_data;
			unset($tmp);
			if($i==$j-1){
				$tree[$i]['last_node'] = 1;
			}
			unset($out_data);
		}
		return $tree;
		
	}
	
	
	public function fetch(){
		return $this->get_tree();
	}
	
	
	function __destruct() {
	
	}
}

?>