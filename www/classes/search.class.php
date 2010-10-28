<?php


require_once 'classes/abstract/module.abstract.class.php';
require_once 'classes/filters.class.php';



class search extends module_abstract {

	protected $page = 1;
	protected $items_per_page;
	protected $current_items;
	protected $max_to_show_all = 50;
	
	
	function __construct() {
		parent::__construct();
	}
	
	protected function parse_price($dig){
		$str = '';
		$str .= $dig;
		$j = strlen($str);
		$out = array();
		for($k=0;$k<$j;$k++)$out[$k]['value'] = $str[$k];
		return $out;
	}
	
	public function get_path($data){
		$out = array();
		$i = 0;
		if($data['class']!=catalog_data_variables::$defaults['class']){
			if($data['node']!=catalog_data_variables::$defaults['node']){
				if($this->check_node($data['node'])){
					
					$this->db->query(sprintf("SELECT tree_id,tree_name,tree_group FROM catalog_tree WHERE tree_id = '%d' LIMIT 1;",$data['node']));
					$node = $this->db->fetch_one();
					$group = false;
					if($node['tree_group']!=0){
						$this->db->query(sprintf("SELECT tree_group_name,tree_group_id FROM catalog_tree_groups WHERE tree_group_id = '%d' LIMIT 1;",$node['tree_group']));
						$group = $this->db->fetch_one();
					}
					$i = 0;
					if($group){
						$out[$i++] = array("name" => $group['tree_group_name'],"url"=>sprintf("?group=%d",$group['tree_group_id']));
					}
					$out[$i++] = array("name" => $node['tree_name'],"url"=>sprintf("?group=%d&node=%d",$group['tree_group_id'],$node['tree_id']));
				}else{
					$this->db->query(sprintf("SELECT * FROM kis_classes WHERE class_id = '%d'LIMIT 1;",$data['class']));
					$class = $this->db->fetch_one();
					$this->db->query(sprintf("SELECT tree_id,tree_name,tree_group FROM catalog_tree WHERE tree_id = '%d' LIMIT 1;",$data['node']));
					$node = $this->db->fetch_one();
					$group = false;
					if($node['tree_group']!=0){
						$this->db->query(sprintf("SELECT tree_group_name,tree_group_id FROM catalog_tree_groups WHERE tree_group_id = '%d' LIMIT 1;",$node['tree_group']));
						$group = $this->db->fetch_one();
					}
					
					if($group){
						$out[$i++] = array("name" => $group['tree_group_name'],"url"=>sprintf("?group=%d",$group['tree_group_id']));
					}
					$out[$i++] = array("name" => $node['tree_name'],"url"=>sprintf("?group=%d&node=%d",$group['tree_group_id'],$node['tree_id']));
					$out[$i++] = array("name" => $class['class_name'],"url"=>sprintf("?group=%d&node=%d&class=%d",$group['tree_group_id'],$node['tree_id'],$class['class_id']));
					
				}
			}elseif($data['group']!=catalog_data_variables::$defaults['group']){
				$this->db->query(sprintf("SELECT tree_group_name,tree_group_id FROM catalog_tree_groups WHERE tree_group_id = '%d' LIMIT 1;",$data['group']));
				$group = $this->db->fetch_one();
				$out[$i++] = array("name" => $group['tree_group_name'],"url"=>sprintf("?group=%d",$group['tree_group_id']));
			}
		}elseif($data['node']!=catalog_data_variables::$defaults['node']){
			$this->db->query(sprintf("SELECT tree_id,tree_name,tree_group FROM catalog_tree WHERE tree_id = '%d' LIMIT 1;",$data['node']));
			$node = $this->db->fetch_one();
			$group = false;
			if($node['tree_group']!=0){
				$this->db->query(sprintf("SELECT tree_group_name,tree_group_id FROM catalog_tree_groups WHERE tree_group_id = '%d' LIMIT 1;",$node['tree_group']));
				$group = $this->db->fetch_one();
			}
			$i = 0;
			if($group){
				$out[$i++] = array("name" => $group['tree_group_name'],"url"=>sprintf("?group=%d",$group['tree_group_id']));
			}
			$out[$i++] = array("name" => $node['tree_name'],"url"=>sprintf("?group=%d&node=%d",$group['tree_group_id'],$node['tree_id']));
		}elseif($data['group']!=catalog_data_variables::$defaults['group']){
			$this->db->query(sprintf("SELECT tree_group_name,tree_group_id FROM catalog_tree_groups WHERE group_id = '%d' LIMIT 1;",$data['group']));
			$group = $this->db->fetch_one();
			$out[$i++] = array("name" => $group['tree_group_name'],"url"=>sprintf("?group=%d",$group['tree_group_id']));
		}
		$out[$i-1]['last_chain'] = true;
		return $out;		
	}
	
	public function get_pager(){
		
		$ci = $this->current_items;
		$ipp = $this->items_per_page;
		$page_num = (int)($ci/$ipp) + (($ci%$ipp != 0)? (1):(0));
		$data = array();
		$next = array();
		for($i=1;$i<=$page_num;$i++){
			$data[$i-1]['page'] = $i;
			$data[$i-1]['url_inc'] = 'page='.$i;
			if($this->page == $i){
				if($i!=$page_num)$next['url_inc_next'] = 'page='.($i+1);
				if($i!=1)$next['url_inc_prev'] = 'page='.($i-1);
				$data[$i-1]['is_current'] = true;
			}
		}
		if($ci<=$this->max_to_show_all){ 
			$data[] = array('show_all' => 1,'url_inc'=>'page=-1','is_current'=>($this->page==-1)?true:false);
		}
		$data['prev_next'] = $next;
		return $data;
	}
	
	public function get_new_goods($num=3){//есть num == 0 то выбираем все
		$query = "
			SELECT
				segment_wares.warecode
			FROM 
				segments
				JOIN 
					segment_regions 
				ON 
					segment_regions.segment_id = segments.segment_id
				JOIN 
					segment_wares 
				ON 
					segment_wares.sg_id = segment_regions.sg_id
			WHERE 
				segments.segment_name = 'novelty'
				AND 
				segment_regions.region_id = %d
				ORDER BY start_time asc %s;";
		$this->db->query(sprintf($query,$this->region,($num!=0)?("LIMIT $num"):('')));
		$ids = $this->db->fetch_all_result();
		$j = count($ids);
		$data = array();
		$parse_str = function($dig){
				$str = '';
				$str .= $dig;
				$j = strlen($str);
				$out = array();
				for($k=0;$k<$j;$k++)$out[$k]['value'] = $str[$k];;
				return $out;
		};
		for($i=0;$i<$j;$i++){
			$data[$i] = $this->get_good($ids[$i]['warecode']);
			if($data[$i]['prices']['price']){
				$data[$i]['prices']['formated_actual_price'] = $parse_str($data[$i]['prices']['price']);
			} elseif($data[$i]['prices']['old_price']){

				$data[$i]['prices']['formated_actual_price'] = $parse_str($data[$i]['prices']['old_price']);				
			}
		}
		return $data;		
		
	}
	
	
	public function get_goods_of_day(){
		
  		$query = "
			SELECT
				start_time - now() AS deal_day,
				segment_wares.warecode as goods_id,
				param,
				end_time AS end_time,
				start_time AS start_time
			FROM 
				segments
				JOIN 
					segment_regions 
				ON 
					segment_regions.segment_id = segments.segment_id
				JOIN 
					segment_wares 
				ON 
					segment_wares.sg_id = segment_regions.sg_id
			WHERE 
				segments.segment_name = 'deal_of_the_day'
				AND 
				segment_regions.region_id = %d
				AND start_time BETWEEN now() - '2 DAY'::INTERVAL AND now() + '1 DAY'::INTERVAL
				ORDER BY start_time;
		";
  		
		$this->db->query(sprintf($query,$this->region));
		$i = 0;
		$price_formated = function($param){
			$price_nf = explode("\n",$param);
			$formated['min_price'] = $price_nf[2];
			$formated['max_price'] = $price_nf[3];
			$formated['discount'] = $formated['max_price'] - $formated['min_price'];
			$parse_str = function($dig){
				$str = '';
				$str .= $dig;
				$j = strlen($str);
				$out = array();
				for($k=0;$k<$j;$k++)$out[$k]['value'] = $str[$k];;
				return $out;
			};
			$formated['min_price_arr'] = $parse_str($formated['min_price']);
			$formated['max_price_arr'] = $parse_str($formated['max_price']);
			$formated['discount_arr'] = $parse_str($formated['discount']);
			return $formated;
		}; 
		$tmp = $this->db->fetch_all_result();
		$m = count($tmp);
		for($i=0;$i<$m;$i++){
			$data[$i] = $this->get_good($tmp[$i]['goods_id']);
			$k = count($data[$i]['desc']);
			for($j=0;$j<$k;$j++){
				$sep = ($j<$k-1)?(","):('');
				$data[$i]['desc'][$j]['comp_data'] = $data[$i]['desc'][$j]['property_name'].": ".$data[$i]['desc'][$j]['desc_value_mixed'].$sep; 
			}
			$data[$i]['deal'] = $tmp[$i];
			$data[$i]['deal']['proc'] = ($data[$i]['prices']['quantity']==0)?(0):((int)($data[$i]['prices']['quantity_sold']/$data[$i]['prices']['quantity']*100));
			$data[$i]['deal']['prices_formated'] = $price_formated($data[$i]['deal']['param']);
			$data[$i]['deal']['countdown'] = strtotime($data[$i]['deal']['end_time']) -  time();
		};
		return $data;
		
		
		
	}
	
	protected function get_good($good_id,$full=false,$desc_limit=0){
		/*if($this->is_cached($good_id)){
			return $this->goods_container[$good_id]['data'];
		};*/
		$query_select_good = "SELECT * FROM kis_catalog WHERE goods_id = '%d' LIMIT 1;";
		$query_select_group = "SELECT kis_groups.* FROM kis_groups LEFT JOIN kis_catalog_index ON kis_catalog_index.group_id = kis_groups.group_id  WHERE kis_catalog_index.catalog_id = '%d' LIMIT 1;";
		$query_select_desc = "
			SELECT 
				kis_desclist_index.*, 
				kis_properties.property_name, 
				kis_property_groups.property_group_name
			FROM 
				kis_desclist_index
				LEFT JOIN
					kis_properties
				ON
					kis_properties.property_name_id = kis_desclist_index.property_name_id
				LEFT JOIN
					kis_property_groups
				ON
					kis_property_groups.property_group_id = kis_desclist_index.property_group_id

			WHERE
					kis_desclist_index.goods_id = '%d' %s
			ORDER BY kis_desclist_index.desc_order ASC  %s;";
		$query_select_price = "SELECT * FROM kis_prices WHERE goods_id = %d AND region_id = %d;";
		

		$this->db->query(sprintf($query_select_good,$good_id));
		$tmp = $this->db->fetch_all_result();
		$data = $tmp[0];

		$this->db->query(sprintf($query_select_group,$good_id));
		$tmp = $this->db->fetch_all_result();
		$data['group'] = $tmp[0];
		
		$this->db->query(sprintf($query_select_price,$good_id,$this->region));
		$tmp = $this->db->fetch_all_result();
		$data['prices'] = $tmp[0];		
		if(!isset($data['desc_cache']) || $data['desc_cache']==''){
			$this->db->query(sprintf($query_select_desc,$good_id,(!$full)?("AND kis_desclist_index.desc_short_descr = 1"):(''),($desc_limit!=0)?("LIMIT $desc_limit"):('')));
			$tmp = $this->db->fetch_all_result();
			$data['desc'] = $tmp;	
					
		}else $this->update_desc_cache($data['desc'],$data['goods_id']);
		return $data;
		
	}
	
	protected function get_goods_by_class($dir,$class){
		$data = array();
		$this->db->query(sprintf("
		SELECT 
			kis_catalog.*,
			kis_prices.*
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			kis_catalog.goods_id IN (
				SELECT
					goods_id
				FROM
					kis_catalog_index
				WHERE
					dir_id = %d
					AND
					class_id = %d	
			) 
			AND	kis_prices.region_id = '%d';",$dir,$class,$this->region));		
		$j = count($data);
		$descs = array();
		for($i=0;$i<$j;$i++){
			if($data[$i]['desc_cache']!= ''){
				$data[$i]['desc'] = unserialize($data[$i]['desc_cache']);	
			}else{
				$this->db->query(sprintf($query_select_desc,$data[$i]['goods_id']));
				$data[$i]['desc'] = $this->db->fetch_all_result();
				$descs[$data[$i]['goods_id']] = $data[$i]['desc'];
			}
		}
		$this->update_desc_cache($descs);
		return $data;
	}
	
	/**
	 * Если в ноде 1 класс, то надо выводить товары, если больше, то классы
	 * true если класс 1
	 */
	protected function check_node($node){
		$query_check = "
			SELECT
				CASE WHEN count(distinct(class_id)) <= 1 THEN 1 ELSE 0 END as answ
			FROM 
				catalog_tree_relations
			WHERE
				tree_id = %d;";
		$this->db->query(sprintf($query_check,$node));
		$tmp = $this->db->fetch_all_result();
		return $tmp[0]['answ'] == 1 ? true : false;
	}
	
	protected function  get_node_info($node,$class=0,$group=0){
		if($node!=catalog_data_variables::$defaults['node']&&$class==catalog_data_variables::$defaults['class']&&$group==catalog_data_variables::$defaults['group']){
			$this->db->query(sprintf("SELECT catalog_tree.tree_name as name FROM catalog_tree WHERE tree_id = '%d';",$node));
			return $this->db->fetch_all_result();
		}elseif($group!=0){
			$this->db->query(sprintf("SELECT catalog_tree_groups.tree_group_name as name FROM catalog_tree_groups WHERE tree_group_id = '%d';",$group));
			return $this->db->fetch_all_result();
		}
	}
	
	public function get_info($data){
		$this->page = $data['page'];
		$this->items_per_page = $data['items_per_page'];
		if($data['searchword']!=catalog_data_variables::$defaults['searchword']){
			return $this->search_by_keyword($data);
		}
		if($data['node']!=catalog_data_variables::$defaults['node']){
			if($data['class']!=catalog_data_variables::$defaults['class']){
				$out = $this->get_goods_by_node_class($data['node'],$data['class']);
				$out['pager']['items'] = $this->get_pager();
				return $out;
			}
			if($this->check_node($data['node'])){
				$out = $this->get_goods_by_node($data['node']);
				$out['pager']['items'] = $this->get_pager();								 		
				return $out;
			}else{
				return $this->get_classes_by_node($data['node']);
			}
		}elseif($data['group']!=catalog_data_variables::$defaults['group']){
			return array("node_info"=>$this->get_node_info(0,0,$data['group']),"data"=>$this->get_classes_by_group($data['group']));
		}
	}
	

	
	protected function get_goods_by_node_class($node,$class){
		$query_select_desc = "
			SELECT 
				kis_desclist_index.desc_value_mixed, 
				kis_properties.property_name, 
				kis_property_groups.property_group_name
			FROM 
				kis_desclist_index
				LEFT JOIN
					kis_properties
				ON
					kis_properties.property_name_id = kis_desclist_index.property_name_id
				LEFT JOIN
					kis_property_groups
				ON
					kis_property_groups.property_group_id = kis_desclist_index.property_group_id

			WHERE
					kis_desclist_index.goods_id = '%d' AND kis_desclist_index.desc_short_descr = 1
			ORDER BY kis_desclist_index.desc_order ASC;";
		
		$query_select_price = "SELECT * FROM kis_prices WHERE goods_id = %d AND region_id = %d;";

		$this->db->query(sprintf("
		SELECT 
			kis_catalog.*,
			kis_prices.*
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			kis_catalog.goods_id IN (
				SELECT 
					catalog_tree_relations_full.goods_id 
				FROM 
					catalog_tree_relations_full 
					LEFT JOIN 
						catalog_tree_relations 
					ON 
						catalog_tree_relations_full.relation_id = catalog_tree_relations.relation_id 
				WHERE
					catalog_tree_relations.tree_id = '%d'
					AND
					catalog_tree_relations.class_id  = '%d'
				) 
			AND	kis_prices.region_id = '%d' %s;",$node,$class,$this->region,($this->page!=-1)?(sprintf(" OFFSET %d LIMIT %d",($this->page-1)*$this->items_per_page,$this->page*$this->items_per_page)):('')));
		$data = $this->db->fetch_all_result();
		$this->db->query(sprintf("
		SELECT 
			count(kis_catalog.*) as cnt
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			kis_catalog.goods_id IN (
				SELECT 
					catalog_tree_relations_full.goods_id 
				FROM 
					catalog_tree_relations_full 
					LEFT JOIN 
						catalog_tree_relations 
					ON 
						catalog_tree_relations_full.relation_id = catalog_tree_relations.relation_id 
				WHERE
					catalog_tree_relations.tree_id = '%d'
					AND
					catalog_tree_relations.class_id  = '%d'
				) 
			AND	kis_prices.region_id = '%d';",$node,$class,$this->region));

		$tmp = $this->db->fetch_all_result();
		$this->current_items = $tmp[0]['cnt'];
		
		$j = count($data);
		$descs = array();
		for($i=0;$i<$j;$i++){
			if($data[$i]['desc_cache']!= ''){
				$data[$i]['desc'] = unserialize($data[$i]['desc_cache']);
				$t = count($data[$i]['desc']);
				for($g = 0;$g<$t;$g++){
					if($data[$i]['desc'][$g]['property_name']=='')unset($data[$i]['desc'][$g]);	
				}
			}else{
				$this->db->query(sprintf($query_select_desc,$data[$i]['goods_id']));
				$data[$i]['desc'] = $this->db->fetch_all_result();
				$descs[$data[$i]['goods_id']] = $data[$i]['desc'];
				$t = count($data[$i]['desc']);
				for($g = 0;$g<$t;$g++){
					if($data[$i]['desc'][$g]['property_name']=='')unset($data[$i]['desc'][$g]);	
				}
			}
			$data[$i]['price_formated'] = $this->parse_price($data[$i]['price']);
			$data[$i]['old_price_formated'] = $this->parse_price($data[$i]['old_price']);
			
		}
		$this->update_desc_cache($descs);
		return array("data" => $data);
	
	
	}
	
	
	protected function get_goods_by_node($node=0,$group=0){
		if(isset($group)&&$group!=0){
			return $this->get_classes_by_group($group);		
		};

		$query_select_desc = "
			SELECT 
				kis_desclist_index.desc_value_mixed, 
				kis_properties.property_name, 
				kis_property_groups.property_group_name
			FROM 
				kis_desclist_index
				LEFT JOIN
					kis_properties
				ON
					kis_properties.property_name_id = kis_desclist_index.property_name_id
				LEFT JOIN
					kis_property_groups
				ON
					kis_property_groups.property_group_id = kis_desclist_index.property_group_id

			WHERE
					kis_desclist_index.goods_id = '%d' AND kis_desclist_index.desc_short_descr = 1
			ORDER BY kis_desclist_index.desc_order ASC;";
		
		$query_select_price = "SELECT * FROM kis_prices WHERE goods_id = %d AND region_id = %d;";
		$query_count_goods = "
		SELECT 
			count(kis_catalog.*) as cnt
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			kis_catalog.goods_id IN (
				SELECT 
					catalog_tree_relations_full.goods_id 
				FROM 
					catalog_tree_relations_full 
					LEFT JOIN 
						catalog_tree_relations 
					ON 
						catalog_tree_relations_full.relation_id = catalog_tree_relations.relation_id 
				WHERE
					catalog_tree_relations.tree_id = '%d'
				) 
			AND	kis_prices.region_id = '%d';
		";
		$this->db->query(sprintf("
		SELECT 
			kis_catalog.*,
			kis_prices.*
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			kis_catalog.goods_id IN (
				SELECT 
					catalog_tree_relations_full.goods_id 
				FROM 
					catalog_tree_relations_full 
					LEFT JOIN 
						catalog_tree_relations 
					ON 
						catalog_tree_relations_full.relation_id = catalog_tree_relations.relation_id 
				WHERE
					catalog_tree_relations.tree_id = '%d'
				) 
			AND	kis_prices.region_id = '%d' %s;",$node,$this->region,($this->page!=-1)?(sprintf(" OFFSET %d LIMIT %d",($this->page-1)*$this->items_per_page,$this->page*$this->items_per_page)):('')));
		$data = $this->db->fetch_all_result();
		
		$this->db->query(sprintf($query_count_goods,$node,$this->region));
		$tmp = $this->db->fetch_all_result();
		$this->current_items = $tmp[0]['cnt'];
		
		$j = count($data);
		$descs = array();
		for($i=0;$i<$j;$i++){
			if($data[$i]['desc_cache']!= ''){
				$data[$i]['desc'] = unserialize($data[$i]['desc_cache']);
				$t = count($data[$i]['desc']);
				for($g = 0;$g<$t;$g++){
					if($data[$i]['desc'][$g]['property_name']=='')unset($data[$i]['desc'][$g]);	
				}
			}else{
				$this->db->query(sprintf($query_select_desc,$data[$i]['goods_id']));
				$data[$i]['desc'] = $this->db->fetch_all_result();
				$descs[$data[$i]['goods_id']] = $data[$i]['desc'];
				$t = count($data[$i]['desc']);
				for($g = 0;$g<$t;$g++){
					if($data[$i]['desc'][$g]['property_name']=='')unset($data[$i]['desc'][$g]);	
				}
			}
			$data[$i]['price_formated'] = $this->parse_price($data[$i]['price']);
			$data[$i]['old_price_formated'] = $this->parse_price($data[$i]['old_price']);
		}
		$this->update_desc_cache($descs);
		return array("data" => $data);
	}
	

	protected function get_classes_by_node($node){
		$query_select_relations_by_node = "SELECT catalog_tree_relations.* FROM catalog_tree_relations WHERE catalog_tree_relations.tree_id = '%d';";
		$query_select_classes_by_full_info = "
			SELECT 
				distinct(kis_classes.class_id) as class_id, 
				kis_classes.class_name, 
				(
					SELECT 
						count(*) 
					FROM 
						kis_catalog_index 
					WHERE
						kis_catalog_index.dir_id = %d 
						AND 
						kis_catalog_index.class_id = %d
				) as class_items 
			FROM 
				kis_classes 
				LEFT JOIN 
					kis_catalog_index 
				ON 
					kis_catalog_index.class_id = kis_classes.class_id 
			WHERE 
				kis_catalog_index.dir_id = %d
				AND 
				kis_catalog_index.class_id = %d 
			GROUP BY 
				kis_classes.class_id,
				kis_classes.class_name;";
		$this->db->query(sprintf($query_select_relations_by_node,$node));
		$relations = $this->db->fetch_all_result();
		$j = count($relations);
		$data = array();
		for($i=0;$i<$j;$i++){
				$this->db->query(sprintf($query_select_classes_by_full_info,$relations[$i]['dir_id'],$relations[$i]['class_id'],$relations[$i]['dir_id'],$relations[$i]['class_id']));
				$data = array_merge($data,$this->db->fetch_all_result());
		}
		return $data;
	}
	
	
	
	
	protected function get_classes_by_group($group){
		$query_select_relations_by_group = "SELECT catalog_tree_relations.* FROM catalog_tree_relations WHERE catalog_tree_relations.tree_id IN (SELECT tree_id FROM catalog_tree WHERE tree_group = '%d' );";
		$query_select_classes_by_full_info = "
			SELECT 
				distinct(kis_classes.class_id) as class_id, 
				kis_classes.class_name, 
				kis_catalog_index.dir_id,
				(
					SELECT 
						count(*) 
					FROM 
						kis_catalog_index
						LEFT JOIN kis_prices
						ON kis_prices.goods_id = kis_catalog_index.catalog_id 
					WHERE
						kis_catalog_index.dir_id = %d 
						AND 
						kis_catalog_index.class_id = %d
						AND
						kis_prices.region_id = '%d'
				) as class_items,
				catalog_tree_relations.tree_id
			FROM 
				kis_classes 
				LEFT JOIN 
					kis_catalog_index 
				ON 
					kis_catalog_index.class_id = kis_classes.class_id
				LEFT JOIN
					catalog_tree_relations
				ON
					kis_catalog_index.class_id = catalog_tree_relations.class_id AND
					kis_catalog_index.dir_id   = catalog_tree_relations.dir_id			 
			WHERE 
				kis_catalog_index.dir_id = %d
				AND 
				kis_catalog_index.class_id = %d 
			GROUP BY 
				kis_classes.class_id,
				kis_classes.class_name,
				catalog_tree_relations.tree_id,
				kis_catalog_index.dir_id;";
		
		$query_select_marks = "
		SELECT 
			distinct(kis_catalog.mark_id) as mark_id,
			kis_marks.mark_name as mark_name
		FROM
			catalog_tree_relations_full
		LEFT JOIN
			kis_catalog
			ON
				kis_catalog.goods_id = catalog_tree_relations_full.goods_id
		LEFT JOIN 
			kis_marks
			ON
				kis_catalog.mark_id = kis_marks.mark_id
		WHERE
			catalog_tree_relations_full.relation_id IN (


				SELECT distinct(catalog_tree_relations.relation_id) FROM catalog_tree_relations WHERE catalog_tree_relations.tree_id IN (
					SELECT tree_id FROM catalog_tree WHERE tree_group = '1' 
				)
			);";
		

		$data = array();
		$this->db->query($query_select_marks,$group);
		$marks['items'] = $this->db->fetch_all_result();
		$j=count($marks['items']);
		for($i=0;$i<$j;$i++){
			$marks['items'][$i]['iteration'] = $i+1;
			$marks['items'][$i]['group'] = $group;
		}
		$marks['cnt'] = count($marks['items']);
				
		$this->db->query(sprintf($query_select_relations_by_group,$group));
		$relations = $this->db->fetch_all_result();
		$j = count($relations);
		for($i=0;$i<$j;$i++){
				$this->db->query(sprintf($query_select_classes_by_full_info,$relations[$i]['dir_id'],$relations[$i]['class_id'],$this->region,$relations[$i]['dir_id'],$relations[$i]['class_id']));
				$tmp = $this->db->fetch_all_result();
				$data = array_merge($data,$tmp);
		}
		$cnt = count($data);
		$classes = '';
		for($i=0;$i<$cnt;$i++){
			if($i%2==1)$data[$i]['is_tr'] = true;
			$data[$i]['iteration'] = $i+1;
		}
		
		if($cnt%2 != 0){
			$data[$cnt-1]['last_not_mod_two'] = 1;
		}
		$data_out['classes'] = $data;
		$data_out['marks'] = $marks;
		return $data_out;
	}
	
	protected function get_goods_by_group($group){
		$query_select_desc = "
			SELECT 
				kis_desclist_index.desc_value_mixed, 
				kis_properties.property_name, 
				kis_property_groups.property_group_name
			FROM 
				kis_desclist_index
				LEFT JOIN
					kis_properties
				ON
					kis_properties.property_name_id = kis_desclist_index.property_name_id
				LEFT JOIN
					kis_property_groups
				ON
					kis_property_groups.property_group_id = kis_desclist_index.property_group_id

			WHERE
					kis_desclist_index.goods_id = '%d' AND kis_desclist_index.desc_short_descr = 1
			ORDER BY kis_desclist_index.desc_order ASC;";
		$query_select_price = "SELECT * FROM kis_prices WHERE goods_id = %d AND region_id = %d;";

		$this->db->query(sprintf("
		SELECT 
			kis_catalog.*,
			kis_prices.*
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			kis_catalog.goods_id IN (
				SELECT 
					catalog_tree_relations_full.goods_id 
				FROM 
					catalog_tree_relations_full 
				LEFT JOIN 
					catalog_tree_relations 
				ON 
					catalog_tree_relations_full.relation_id = catalog_tree_relations.relation_id 
				LEFT JOIN 
					catalog_tree 
				ON 
					catalog_tree.tree_id = catalog_tree_relations.tree_id 
				WHERE 
					catalog_tree.tree_group = '%d'
				) 
			AND	kis_prices.region_id = '%d';",$group,$this->region));
		$data = $this->db->fetch_all_result();
		$j = count($data);
		$descs = array();
		for($i=0;$i<$j;$i++){
			if($data[$i]['desc_cache']!= ''){
				$data[$i]['desc'] = unserialize($data[$i]['desc_cache']);	
			}else{
				$this->db->query(sprintf($query_select_desc,$data[$i]['goods_id']));
				$data[$i]['desc'] = $this->db->fetch_all_result();
				$descs[$data[$i]['goods_id']] = $data[$i]['desc'];
			}
			$data[$i]['price_formated'] = $this->parse_price($data[$i]['price']);
			$data[$i]['old_price_formated'] = $this->parse_price($data[$i]['old_price']);
			
		}
		$this->update_desc_cache($descs);
		return $data;
	}
	
	protected function search_by_keyword($data){
		
		$searchword = addslashes($data['searchword']);
		$query_select_desc = "
			SELECT 
				kis_desclist_index.desc_value_mixed, 
				kis_properties.property_name, 
				kis_property_groups.property_group_name
			FROM 
				kis_desclist_index
				LEFT JOIN
					kis_properties
				ON
					kis_properties.property_name_id = kis_desclist_index.property_name_id
				LEFT JOIN
					kis_property_groups
				ON
					kis_property_groups.property_group_id = kis_desclist_index.property_group_id

			WHERE
					kis_desclist_index.goods_id = '%d' AND kis_desclist_index.desc_short_descr = 1
			ORDER BY kis_desclist_index.desc_order ASC;";
		$query_select_price = "SELECT * FROM kis_prices WHERE goods_id = %d AND region_id = %d;";

		$this->db->query(sprintf("
		SELECT 
			kis_catalog.*,
			kis_prices.*
		FROM 
			kis_catalog 
			LEFT JOIN 
				kis_prices 
			ON 
				kis_prices.goods_id = kis_catalog.goods_id 
		WHERE 
			(
				lower(kis_catalog.full_name) like lower('%%%s%%')
				OR
				lower(kis_catalog.descr) like lower('%%%s%%')
			)
			AND
			kis_prices.region_id = '%d' LIMIT 5;",$searchword,$searchword,$this->region));
		$data = $this->db->fetch_all_result();
		$j = count($data);
		$descs = array();
		for($i=0;$i<$j;$i++){
			if($data[$i]['desc_cache']!= ''){
				$data[$i]['desc'] = unserialize($data[$i]['desc_cache']);	
			}else{
				$this->db->query(sprintf($query_select_desc,$data[$i]['goods_id']));
				$data[$i]['desc'] = $this->db->fetch_all_result();
				$descs[$data[$i]['goods_id']] = $data[$i]['desc'];
			}
		}
		$this->update_desc_cache($descs);
		return array("data" => $data);;
		
	}
	
	
	protected function update_desc_cache($desc,$goods_id=0){
		$query = "UPDATE kis_catalog SET desc_cache = '%s' WHERE goods_id = %d;\n";
		if(is_array($desc)){
			$cnt = 0;
			$res_query = '';
			foreach($desc as $gid => $did){
				$res_query .= sprintf($query,serialize($did),$gid);
				if($cnt==20){
					$this->db->query($res_query);
					$res_query = '';
					$cnt = 0;
				}else{
					$cnt++;
				}
			}
			if($res_query!=''){
				$this->db->query($res_query);
				
			}
			return;
		}
		$this->db->query(sprintf($query,serialize($desc),$goods_id));
	}
	
	
	
	
	function fetch(){
		
		
		return false;
	}
	
	
	
}

?>