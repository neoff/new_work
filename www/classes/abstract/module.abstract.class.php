<?php
include_once 'classes/service_config.php';
include_once 'classes/Exception.class.php';
include_once 'classes/pg_db.class.php';
include_once 'classes/service/log.class.php';
include_once 'classes/template.class.php';




class catalog_data_variables {
	
	static public $defaults = array(
		"node" => 0,
		"group"   => 0,
		"good"=> 0,
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

	protected $variable = array();
	protected $except = array();
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
	 * перегружаем атрибуты,
	 * устанавливает public атрибут с именем $name и значением $val
	 * 
	 * @param $name - имя
	 * @param $val - значение
	 * 
	 */
	public function __set($name, $val)
	{
		$this->$name = $val;
	}
	
	/**
	 * перегружаем атрибуты
	 * возвращает атрибут если он есть или false
	 * 
	 * @param $var_name - имя атрибута
	 */
	public function __get($var_name){
		$this->variable;
		if(is_array($this->variable))
			if(!empty($this->variable))
				if(array_key_exists($var_name, $this->variable))
					return $this->variable[$var_name];
		elseif(property_exists('module_abstract', $var_name))
			return $this->$var_name;
		else
			return $this->$var_name="";
	}
	
	

	/**
	 * проверка параметров
	 * вносит в переменную except ошибки
	 * 
	 * @param $name - название ключа в except
	 * @param $data - проверяемое значение
	 * @param $mask - маска для проверки
	 */
	public function checker($name, $data, $mask="^\w+")
	{
		$this->except;
		//print_r($this->except);
		if(!preg_match("/$mask/", $data)){
			$this->except["error_".$name] = True;
		}
	}

	/**
	 * ����������� �������, ������� ������ ���� � ������ ������, �������� �������.
	 * ������ ���������� ������������ ������
	 * 
	 */
	abstract public function fetch();
	
	
	
	
	
	function __destruct() {
		$this->db->disconnect();	
	}
}

?>