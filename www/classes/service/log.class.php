<?php

include_once 'classes/pg_db.class.php';


define('CUSTOM_LOG_FATAL_ERROR',1);
define('CUSTOM_LOG_SOFT_ERROR',2);
define('CUSTOM_LOG_NOTICE',3);

define('CUSTOM_LOG_PATH',"/logs/");
define('CUSTOM_LOG_FILE_NAME_FATAL','fatal.error.log');
define('CUSTOM_LOG_FILE_NAME_SOFT','soft.error.log');
define('CUSTOM_LOG_FILE_NAME_NOTICE','notice.log');


/*
 * Формат таблицы с логами
 * DATE
 * MESSAGE
 * URL
 * TYPE (LOG_*)  
 */

interface log_interface {
	public function write_log($message);
}

/**
 * Класс для ведения логов в ФС
 *
 */

class log_fs implements log_interface {
	
	private $type = CUSTOM_LOG_FATAL_ERROR;
	private $file_name = CUSTOM_LOG_FILE_NAME_FATAL;
	private $log_path = CUSTOM_LOG_PATH;
	private $log_format = "DATE | MESSAGE | URL";

	function __construct($type,$path){
		$this->type = $type;
		if($path!==NULL){
			$this->log_path = $path;
		}
		switch ($type) { 
			case CUSTOM_LOG_SOFT_ERROR:
				$this->file_name = CUSTOM_LOG_FILE_NAME_SOFT;
			break;
			case CUSTOM_LOG_NOTICE:
				$this->file_name = CUSTOM_LOG_FILE_NAME_NOTICE;
			break;
		}
	}
	
	private function format_message($message){
		$out_string = str_replace("DATE", date("d-m-Y H:i:s"),$message);
		$out_string = str_replace("MESSAGE", $message,$out_string);
		$out_string = str_replace("URL",$_SERVER["SCRIPT_URI"],$out_string);
		return $out_string;
	}
	
	public function write_log($message){
		if(!is_dir($this->log_path))die('Directory '.$this->log_path.' not exist!');
		$f = fopen($this->log_path.$this->file_name,"w+");
		$log_message = $this->format_message($message);
		fputs($f,$log_message,count_chars($log_message));
	}

}

/**
 * Класс для ведения логов в БД
 *
 */

class log_db implements log_interface {
	
	
	/**
	 * БД
	 *
	 * @var db_controller
	 */
	private $db = NULL;
	private $type = CUSTOM_LOG_FATAL_ERROR;
	
	/**
	 * 
	 */
	function __construct($type,$db_config) {
		$this->db = new db_controller($db_config);
		try{
			$this->db->connect();
		}
		catch(db_connection_error $conn_error){
			$log = log_factory::get_log_instance();
			$log->write_log($conn_error);
			die();
		};
		$this->type = $type;
	}
	
	public function write_log($message){
		$query = sprintf("insert into logs (date,message,url,type) values ('%d','%s','%s','%d')",time(),addslashes($message),addslashes($_SERVER["SCRIPT_URI"]),$this->type);
		try{
			$this->db->query($query);
		}
		catch (db_query_error $query_error){
			$log = log_factory::get_log_instance();
			$log->write_log($query_error);
			die();
		}
		
	}
	
}


class log_factory {

	/**
	 * Возвращает инстанс класса логов, если задан $db_config то будет производиться запись в БД, иначе в ФС
	 * 
	 * @return class log_interface
	 */

	public static function get_log_instance($type=CUSTOM_LOG_FATAL_ERROR,$db_config = NULL,$log_path = NULL){
		if($db_config!==NULL){
			return new log_db($type,$db_config);
		}
		else {
			return new log_fs($type,$log_path);
		}
	}

}

?>