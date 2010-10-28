<?php
include_once 'classes/pg_db.class.php';
include_once 'classes/service/log.class.php';
include_once 'classes/template.class.php';


class catalog_data_variables {
	
	static public $defaults = array(
		"node" => 0,
		"group"   => 0,
		"goods_id"=> 0,
		"class"   => 0,
		"dir"     => 0,
		"mark"    => 0,
		"page"    => 1,
		"items_per_page" => 10,
		"searchword" => ''
	/**
	 * TODO: заполнить полностью массив
	 */
	
	);
	
	
	static public function get_vars(&$array){
		foreach(catalog_data_variables::$defaults as $idx => $data){
			if(!isset($_REQUEST[$idx]) || $_REQUEST[$idx] == ''){
				$array[$idx] = $data;
			}else{
				$array[$idx] = $_REQUEST[$idx];
			}
		}
	}
	
}

/**
 * ������� ����������� ����� ��� ���� �������
 *
 */
abstract class module_abstract {

	protected $args = Array(); // ������������� ������ � ����������� ������������ ��� ������ ������
	protected $array_args = Array();
	protected $db_config = array();
	protected $this_site = "http://www.mvideo.ru";
	/**
	 * @var template
	 */
	protected $template;
	protected $region;
	/**
	 * ���������� ��� ������ ��
	 *
	 * @var pg_db
	 */
	protected $db = NULL;
	
	/**
	 * ���������� ��� ������ �����
	 *
	 * @var log_interface
	 */
	protected $log = NULL;
	
	/**
	 * ����������� ������ ��������� ������ db_controller � ���� ����� $db_config, �� �������������� ����������
	 *
	 * @param array() $db_config
	 */
	function __construct() {
			$this->db = new pg_db();
			$this->template = new template();
			$this->template_dir = $_SERVER['DOCUMENT_ROOT']."/templates/";
			$this->template_extention = '.html';
			$this->region = 1;	
	}
	
	
	
	/**
	 * ������� ����� �������� ���������� � ������� $this->args
	 *
	 * @param string $var_name
	 * @param void $value
	 */
	public function set_var($var_name,$value,$is_array_var = false) {
		if($is_array_var){
			$this->args[$var_name] = $value;
			return; 
		};
		/*if(empty($var_name) || is_array($var_name))
			throw new Exception('Oh SHI-, it`s array! 0_o use function set_vars($var_array)'); // �����������, ���� ������ ��� ��� ������*/
		$this->args[$var_name] = $value;
		
	}

	/**
	 * ������� ����� ��������� ����������. �� ������� ������ ����������.
	 *
	 * @param Array ('var_name' => 'value', ...) $var_array
	 */
	public function set_vars($var_array){
		if(empty($var_array))return;
		if(is_array($var_array)){
				$keys = array_keys($var_array);
				$n = count($keys);
				for ($i=0;$i<$n;$i++){
					if(!empty($keys[$i])){
						$this->args[$keys[$i]] = $var_array[$keys[$i]];					
					}
				}
		}
		else
			throw new Exception('Oh SHI-, it`s NOT array! 0_o use function set_var($var_name,$value)'); // �����������, ���� �� ������
	}
	
	
	/**
	 * ���������� �������� ���������� ������, �� �����
	 *
	 * @param string $var_name
	 * @return void
	 */
	public function get_var($var_name){
		return $this->args[$var_name];
	}
	
	
	/**
	 * "�����" ��������� ������� ���� set_var_name($value) � get_var_name();
	 *
	 * @param string $name
	 * @param string $arguments
	 * @return false || variable value
	 */
	function __call($name,$arguments){
		$regxp_set = "/^set_(.*)/i";
		$regxp_get = "/^get_(.*)/i";
		$matches = array();
		if(0!=preg_match($regxp_set,$name)){//���������, �������� �� ��� ������� set_
			$var_name = $matches[1];
			$value = $arguments;
			$this->set_var($var_name,$value);
			return false;
		}
		elseif(0!=preg_match($regxp_get,$name)){//���������, �������� �� ��� ������� get_
			$var_name = $matches[1];
			$value = $arguments;
			return $this->get_var($var_name);
		}
		return false;
	}

	

	/**
	 * ����������� �������, ������� ������ ���� � ������ ������, �������� �������.
	 * ������ ���������� ������������ ������
	 * 
	 */
	abstract public function fetch();
	
	
	/**
	 * ������� ����� ��������� ��� ���������� � ��, ����� ������������� ���������� ������������
	 * (���� ����) �� �� � ����������� � ������ �����������.
	 * 
	 * �� ������������
	 * @param array() $config
	 */
/*	public function set_db_config($config){
		try{
			$this->db->set_config($config);
		}
		catch (db_invalid_params $invalid_params){
			$this->log = log_factory::get_log_instance(CUSTOM_LOG_FATAL_ERROR,$this->db_config);
			$this->log->write_log($invalid_params->getMessage());
			
			
		}
	}*/
	

	
	
/*	protected function get_template($template_class,$template_name){
		$template_class = $template_class != ''? $template_class."/" : $template_class; 
		return new Blitz($this->template_dir.$template_class.$template_name.$this->template_extention);
	}*/
	
	
	function __destruct() {
		$this->db->disconnect();	
	}
}

?>