<?php

class template extends Blitz{
	
	protected $template_collection = array();
	protected $collection_names = array();
	protected $current_iteration = 0;
	protected $template_dir;
	protected $template_extention;	
	
	function __construct() {
			$this->template_dir = $_SERVER['DOCUMENT_ROOT']."/templates/";
			$this->template_extention = '.html';
	}
	
	public function add_template($template_name,$template_class=''){
		$template_class = $template_class != '' ? $template_class."/" : $template_class;  
		$this->template_collection[$template_class.$template_name] = new Blitz($this->template_dir.$template_class.$template_name.$this->template_extention);
	}
	
	public function set_block($block,$data="",$template_name,$template_class=''){
		$template_class = $template_class != '' ? $template_class."/" : $template_class;
		$this->template_collection[$template_class.$template_name]->Block($block,$data);
	}
	
	public function set_global($var,$template_name,$template_class=''){
		$template_class = $template_class != '' ? $template_class."/" : $template_class;
		$this->template_collection[$template_class.$template_name]->setGlobal($var);
	}
	
	public function set($var,$template_name,$template_class=''){
		$template_class = $template_class != '' ? $template_class."/" : $template_class;
		$this->template_collection[$template_class.$template_name]->set($var);
	}
	
	public function parse ($template_name,$template_class=''){
		$template_class = $template_class != '' ? $template_class."/" : $template_class;
		return $this->template_collection[$template_class.$template_name]->Parse();
	}
	
	function __destruct() {
	
	}
}

?>