<?php

require_once 'classes/abstract/module.abstract.class.php';
require_once 'classes/search.class.php';
require_once 'classes/catalog_tree.class.php';



class mainpage extends module_abstract {
	

	
	function __construct() {
		parent::__construct();
	}
	
	protected function get_tree(){
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
		$search = new search();
		

		$data_deal = $search->get_goods_of_day();

		$data_new['blocks']  = $search->get_new_goods();
		

		$this->template->add_template("header");		
		$this->template->set_global(array("tree_html"=>$this->get_tree(),"site" => $this->this_site),"header");
		$header = $this->template->parse("header");
		
		$this->template->add_template("footer");
		$footer = $this->template->parse("footer");
		
		$this->template->add_template("index");
		$this->template->set_global(array("header"=>$header,"footer"=>$footer,"site"=>$this->this_site),"index");
		$this->template->set_block("deal_of_day",$data_deal[1],"index");
		$this->template->set_block("deal_yesterday",$data_deal[0],"index");
		$this->template->set_block("deal_tomorrow",$data_deal[1],"index");
		$this->template->set_block("new_goods",$data_new,"index");
		echo $this->template->parse("index");
	}
	
	protected function get_header(){
		
		
	}
	
	protected function get_footer(){
		
	}
	
	function __destruct() {
		parent::__destruct();
	}
}

?>