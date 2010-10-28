<?php

include_once 'config.php';

class pg_db {
	
	private $user = DB_USER;
	private $password = DB_PASSWD;
	private $link_id = '';
	private $resource_id = '';
	private $host = DB_HOST;
	private $port = '';
	private $db_name = DB_NAME;
	private $start = 0;
	protected $connection_string = '';

	function __construct() {
		
	}
	
	function set_user($user){
		$this->user = $user;
	}
	
	function set_password($password){
		$this->password = $password;
	}
	
	function set_link_id($link_id){
		$this->link_id = $link_id;
	}
	
	function set_resource_id($rid){
		$this->resource_id = $rid;
	}
	
	function set_host($host){
		$this->host = $host;
	}
	
	function set_port($port){
		$this->port = $port;
	}
	
	function set_db_name($dbname){
		$this->db_name = $dbname;
	}
	
	function connect(){
		if(isset($this->link_id) && !empty($this->link_id))return true;
		$this->connection_string .= (!empty($this->host))?(sprintf(" host=%s",$this->host)):('');
		$this->connection_string .= (!empty($this->port))?(sprintf(" port=%d",$this->port)):('');		
		$this->connection_string .= (!empty($this->db_name))?(sprintf(" dbname=%s",$this->db_name)):('');
		$this->connection_string .= (!empty($this->user))?(sprintf(" user=%s",$this->user)):('');
		$this->connection_string .= (!empty($this->password))?(sprintf(" password=%s",$this->password)):('');
		$this->link_id = pg_pconnect($this->connection_string);
		if($this->link_id)return true;
		return false;
	}
	
	function query($query){
		
		if(!$this->connect())return false;
		$this->resource_id = pg_query($this->link_id,$query);
		if(isset($_REQUEST['debug'])&&$_REQUEST['debug']==1){
			print "====================\nQUERY:\n".$query."\n=======================\n\n";
		}
		if($this->resource_id)return true;

		return false;
	}
	
	function fetch_all_result($resource_id=0){
		if($resource_id){
			$inner_resource = $resource_id;
		}elseif ($this->resource_id)$inner_resource = $this->resource_id;
		if ($inner_resource){
			
			if(!pg_num_fields($inner_resource)){
				
				return false;
			}
			
			$data = pg_fetch_all($inner_resource);
			return $data;
		}else return false;
	}
	
	function fetch_one($resource_id=0){
		if($resource_id){
			$inner_resource = $resource_id;
		}elseif ($this->resource_id)$inner_resource = $this->resource_id;
		if ($inner_resource){
			
			if(!pg_num_fields($inner_resource)){
				
				return false;
			}
			
			$data = pg_fetch_assoc($inner_resource);
			return $data;
		}else return false;
		
	}
	
	
	function num_rows($resource_id=0){
		if($resource_id){
			$inner_resource = $resource_id;
		}elseif ($this->resource_id)$inner_resource = $this->resource_id;
		if ($inner_resource){
			return pg_num_rows($inner_resource);
		}else return false;
		
	}
	
	function get_resource_id(){
		return $this->resource_id;
	}
	
	function get_connection_string(){
		return $this->connection_string;
	}
	
	function last_error(){
		return pg_last_error($this->link_id);
	}
	
	
	function disconnect(){
		if($this->connect()){
			pg_close($this->link_id);
		}
	}
	
	function __destruct() {
		$this->disconnect();
	}
}

?>