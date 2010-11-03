<?php

require_once 'classes/abstract/module.abstract.class.php';
require_once 'classes/search.class.php';
require_once 'classes/catalog_tree.class.php';
include_once 'classes/filters.class.php';





class catalog extends module_abstract {
	
	/**
	 * @var filter
	 */
	
	private $filter;
	
	/**
	 * @var search
	 */
	
	private $search;

	
	function __construct() {
		parent::__construct();
		$this->filter = new filter();
		$this->search = new search();
	}
	
	protected function make_current_url($with_page=false){
		$url = '?';
		foreach(catalog_data_variables::$defaults as $idx => $data){
			if(isset($_REQUEST[$idx]) && $_REQUEST[$idx] != catalog_data_variables::$defaults[$idx]){
				if($idx == 'page'){
					if($with_page)$url = $url."&".$idx."=".$data;
				}else $url = $url.$idx."=".$_REQUEST[$idx]."&";
			}
		}
		return $url;
	}
	
	protected function get_tree($node=0){
		$tree_c = new catalog_tree();
		$tree = $tree_c->fetch();
		$tree_c->__destruct();
		$this->template->add_template("tree_separator","tree");
		$tree_sep =$this->template->parse("tree_separator","tree");
		$this->template->add_template("tree","tree");
		$this->template->set_global(array("site" => $this->this_site),"tree","tree");
		$this->template->set_global(array("tree_separator"=>$tree_sep),"tree","tree");
		$this->template->set_block("tree",array("tree_items"=>$tree),"tree","tree");
		$tree_html = $this->template->parse("tree","tree");
		return $tree_html;
	}

	
	
	
	public function fetch(){
		$start = microtime();
		/* Генерация хедера */
		$this->template->add_template("header");
		$this->template->set_global(array("site"=>$this->this_site,"tree_html"=>$this->get_tree()),"header");
		$header = $this->template->parse("header");

		/*Генерация футера*/
		$this->template->add_template("footer");
		$footer = $this->template->parse("footer");
		/********************************************/
		
		//print_r($this->args);
		$t_name = '';
		if($this->args['good']!=0){
			$t_name = "shop_cart";
		}elseif($this->args['group']!=0){
			$t_name = "classes";	
		}elseif($this->args['node']!=0&&$this->args['class']!=0){
			$t_name = "goods";
		}elseif($this->args['node']!=0||$this->args['searchword']!=''){
			$t_name = "goods";
		}
		
		$path = $this->search->get_path($this->args);
		$this->template->add_template("chain","catalog");
		$this->template->set_block("path_data",array("data"=>$path),"chain","catalog");
		$path_html = $this->template->parse("chain","catalog");
		
		
		$current_url = $this->make_current_url();
		$this->template->add_template($t_name,"catalog");
		$this->template->set(array("footer"=>$footer,"header"=>$header,"site"=>$this->this_site),$t_name,"catalog");
		$data = $this->search->get_info($this->args);
		
		$this->template->add_template("pager","catalog");
		$this->template->set_global(array("current_url"=>$current_url),"pager","catalog");
		$this->template->set_block("data",$data['pager'],"pager","catalog");
		$pager = $this->template->parse("pager","catalog");
		$this->template->set_global(array("site"=>$this->this_site,"path_html"=>$path_html),$t_name,"catalog");
		if($pager){
			$this->template->set_global(array("pager_html"=>$pager),$t_name,"catalog");
		}
		//print_r($data);
		$this->template->set_block("data",$data,$t_name,"catalog");
		//print_r($this->template);
		echo $this->template->parse($t_name,"catalog");
		
		echo "\n\ntotal time = ".(1000*(microtime() - $start))." ms";
	}
	
	
	/**
	 * 
	 */
	function __destruct() {
		parent::__destruct();
	}
}

?>