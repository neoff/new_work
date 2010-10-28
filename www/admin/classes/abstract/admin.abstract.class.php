<?php
include_once 'classes/pg_db.class.php';
include_once 'classes/service/log.class.php';

/**
 * Базовый абстрактный класс для всех модулей
 *
 */
abstract class admin_abstract {

	protected $template_dir;
	protected $args = Array(); // Ассоциативный массив с параметрами необходимыми для работы класса
	protected $array_args = Array();
	protected $db_config = array();
	/**
	 * Переменная для класса БД
	 *
	 * @var pg_db
	 */
	protected $db = NULL;
	
	/**
	 * Переменная для класса Логов
	 *
	 * @var log_interface
	 */
	protected $log = NULL;
	
	/**
	 * Конструктор создаёт экземпляр класса db_controller и если задан $db_config, то инициализирует соединение
	 *
	 * @param array() $db_config
	 */
	function __construct() {
			$this->db = new pg_db();
			$this->template_dir = $_SERVER['DOCUMENT_ROOT']."/templates/admin/";
	}
	
	
	
	/**
	 * Функция задаёт значение переменной в массиве $this->args
	 *
	 * @param string $var_name
	 * @param void $value
	 */
	public function set_var($var_name,$value,$is_array_var = false) {
		if($is_array_var){
			$this->args[$var_name] = $value; 
		};
		if(empty($var_name) || is_array($var_name))
			throw new Exception('Oh SHI-, it`s array! 0_o use function set_vars($var_array)'); // отлавливаем, если пустое имя или массив
		$this->args[$var_name] = $value;
	}

	/**
	 * Функция задаёт несколько переменных. Не очищает список переменных.
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
			throw new Exception('Oh SHI-, it`s NOT array! 0_o use function set_var($var_name,$value)'); // отлавливаем, если не массив
	}
	
	
	/**
	 * Возвращает значение переменной класса, по имени
	 *
	 * @param string $var_name
	 * @return void
	 */
	public function get_var($var_name){
		return $this->args[$var_name];
	}
	
	
	/**
	 * "Отлов" некоторых функций типа set_var_name($value) и get_var_name();
	 *
	 * @param string $name
	 * @param string $arguments
	 * @return false || variable value
	 */
	function __call($name,$arguments){
		$regxp_set = "/^set_(.*)/i";
		$regxp_get = "/^get_(.*)/i";
		$matches = array();
		if(0!=preg_match($regxp_set,$name)){//проверяем, является ли эта функция set_
			$var_name = $matches[1];
			$value = $arguments;
			$this->set_var($var_name,$value);
			return false;
		}
		elseif(0!=preg_match($regxp_get,$name)){//проверяем, является ли эта функция get_
			$var_name = $matches[1];
			$value = $arguments;
			return $this->get_var($var_name);
		}
		return false;
	}

	

	/**
	 * Абстрактная функция, которая должна быть в каждом классе, основная функция.
	 * Должна возвращать обработанные данные
	 * 
	 */
	//abstract public function fetch();
	
	
	/**
	 * Функция задаёт настройки для соединения с БД, после использования происходит отсоединение
	 * (если было) от БД и подключение с новыми параметрами.
	 * 
	 * Не используется
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
	

	function __destruct() {	
	}
}

?>