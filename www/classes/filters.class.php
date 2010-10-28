<?php
/*
 * ���� ��� ��������� � �
 */

class filter_data {
	public $search_string;
	public $group_id;
	public $class_id;
	public $page;
	public $region;
	public $goods_id;	
	public $tree_id;
	public $item_per_page;
}

class filter {
	
	/*
	 * ��������� ��-���������, ������ ��� ���������
	 * ��� �� ����� �� �������� � ����������� ����������
	 */
	static public $NO_SEARCH = false;
	static public $NO_GROUP  = -1;
	static public $NO_CLASS  = -1;
	static public $NO_PAGE   = -1;
	static public $NO_GOODS_ID = -1;
	static public $NO_REGION = -1; 
	static public $NO_TREE_ID = -1;
	static public $DEFAULT_ITEMS_PER_PAGE = 20;
	
	static public $SEARCH_NAME = 'SEARCH_WORD';
	static public $GROUP_NAME  = 'GROUP_ID';
	static public $CLASS_NAME  = 'CLASS_ID';
	static public $PAGE_NAME   = 'PAGE';
	static public $GOODS_NAME = 'GOODS_ID';
	static public $REGION_NAME   = 'REGION';
	static public $TREE_NAME = 'TREE_ID';
	static public $ITEM_PER_PAGE_NAME = 'ITEMS_PER_PAGE'; 
	
	
	/**
	 * @var filter_data
	 */
	public $filter_data;
		
	function __construct(){
		$this->get_from_session(true);
		$this->filter_data = new filter_data();
	}
	
	public function set_defaults(){
		$this->filter_data->search_string = filter::$NO_SEARCH;
		$this->filter_data->group_id = filter::$NO_GROUP;
		$this->filter_data->page = filter::$NO_PAGE;
		$this->filter_data->region = filter::$NO_REGION;
		$this->filter_data->goods_id = filter::$NO_GOODS_ID;
		$this->filter_data->tree_id = filter::$NO_TREE_ID;
	}
	
	public function get_from_session($cookie_first=false){
		$vars = false;
		if($cookie_first){
			if(isset($_COOKIE['FILTER_DATA']))$vars = unserialize($_COOKIE['FILTER_DATA']);
		}
		if(!$vars && isset($_SESSION['FILTER_DATA']))$vars = unserialize($_SESSION['FILTER_DATA']);
		return $vars;	
	}
	
	
	/*
	 * TODO: �������� ���������� ����, ���� �����������.
	 */
	public function write_into_cookies(){
		setcookie("FILTER_DATA",serialize($this->filter_data));	
	}
	
	public function write_into_session(){
		$_SESSION['FILTER_DATA'] = serialize($this->filter_data);
	}
	
	public function reset_filter(){
		$this->set_defaults();
		$this->write_into_cookies();
		$this->write_into_session();
	}
	
	public function get_data_from_post(){
		$arr = $_REQUEST['FILTER_DATA'];
		$this->filter_data->class_id = $arr[filter::$CLASS_NAME];
		$this->filter_data->goods_id = $arr[filter::$GOODS_NAME];
		$this->filter_data->group_id = $arr[filter::$GROUP_NAME];
		$this->filter_data->item_per_page = $arr[filter::$ITEM_PER_PAGE_NAME];
		$this->filter_data->page = $arr[filter::$PAGE_NAME];
		$this->filter_data->region = $arr[filter::$REGION_NAME];
		$this->filter_data->search_string = $arr[filter::$SEARCH_NAME];
		$this->filter_data->tree_id = $arr[filter::$TREE_NAME];
		
	}
	
	public function make_search_array(){
		$arr[filter::$CLASS_NAME] = $this->filter_data->class_id;
		$arr[filter::$GOODS_NAME] = $this->filter_data->goods_id;
		$arr[filter::$GROUP_NAME] = $this->filter_data->group_id;
		$arr[filter::$ITEM_PER_PAGE_NAME] = $this->filter_data->item_per_page;
		$arr[filter::$PAGE_NAME] = $this->filter_data->page;
		$arr[filter::$REGION_NAME] = $this->filter_data->region;
		$arr[filter::$SEARCH_NAME] = $this->filter_data->search_string;
		$arr[filter::$TREE_NAME] = $this->filter_data->tree_id;
		return $arr;
	}
	
}
?>