<?php
/**  
 * 
 * 
 * @package    Register
 * @subpackage privateOffice
 * @since      29.10.2010 11:15:29
 * @author     enesterov
 * @category   controller
 */

	namespace User;
	require_once 'User.interface.php';
	
 /**
 * Реализация регистрации и авторизации пользователя
 * @author enesterov
 *
 */
class Registration extends UserTemplate implements UserRegistration {
	protected $variable = array();
	protected $except = array();
	private $do = False;
	
	public function __construct() {
		$this->tpl_name = "user/user_form";
		parent::__construct();
		$this->template->add_template($this->tpl_name);
	}
	
	public function checkPost()
	{
		if($_POST)
			if(array_key_exists("userdata", $_POST))
				$this->addUser();
			else 
				$this->authUser();
	}
	
	/**
	 * устанавливает данные пользователя в this
	 * и возвращает id
	 */
	public function authUser()
	{
		$this->do = True;
		
		if($_POST)
		{
			$this->variable = $_POST['userlogin'];
			$this->checkUserData();
			
		}
	}
	
	public function addUser()
	{
		if($_POST)
		{
			$this->variable = $_POST['userdata'];
			$this->checkUserData();
			$this->checkUserRegisterData();
			
			if(!$this->pikup)
				$this->checkAddress();
				
			if($this->person!="natural")
				$this->checkYur();
			else 
			{
				$this->person = False;
				$this->variable['person']= 0;
			}
			
			$this->checkEmailExist();
			$this->checkAndRegisterNewUser();
			
		}
	}
	
	/**
	 * возвращает форму пользователя
	 * @param $pikup - в случае если не нужен адрес доставки значение True
	 */
	public function getForm($pikup=False)
	{
		$this->pikup = $pikup;
		
		
		$this->template->set_global(array("site"=>$this->this_site),$this->tpl_name);
		$this->template->set_block("javascript", "", $this->tpl_name);
		
		$this->template->set_global(array("sdf"=>"asd"), $this->tpl_name);
		//вход
		$this->template->add_template("user/login_form");
		$data =($this->do)?array_merge($this->except, $this->variable):array();
		$this->template->set_block("nologin",$data, "user/login_form");
		$login = $this->template->parse("user/login_form");
		
		$this->template->set_block("user_login", array("login_form"=>$login), $this->tpl_name);
		
		//регистрация
		$data =(!$this->do)?array_merge( $this->except, $this->variable):array();
		$this->template->set_block("user_register", $data, $this->tpl_name);
		
		return $this->template->parse($this->tpl_name);
	}
	
	public function forgetPassword(){}
	
	/**
	 * регистрирует нового пользователя, если нету ошибок
	 */
	private function checkAndRegisterNewUser()
	{
		if(!$this->except)
		{
			$this->registerNewUser();
			
			if($this->person)
				$this->registerJurUser();
			
			if(!$this->pikup)
				$this->addDeliveruAddress();
			
		}
	}
	
	/**
	 * принемает значение из БД и устанавливает из в variable
	 * @param (array) $data - выборка из БД
	 */
	private function setUserId($data)
	{
		$this->variable = $data;
		$_SESSION["user_id"] = $this->id;
	}
	
	/**
	 * если существует ИД 
	 * то ф-я обновит все variable
	 */
	private function userAuthExist()
	{
		$sql="select * from users where id=$this->id";
		$this->db->query($sql);
		$data = $this->db->fetch_one();
		$this->setUserId($data);
	}
	
	/**
	 * проверяет существует ли пользователь по введенным логину и паролю
	 */
	private function checkUserExist()
	{
		
		$sql="select * from users where email='$this->mail' and password='$this->newpassword'";
		$this->db->query($sql);
		$data = $this->db->fetch_one();
		if(!is_array($this->db->fetch_one()))
			$this->except["error_user"] = True;
		else 
			$this->setUserId($data);
	}
	
	/**
	 * проверяет существует ли пользователь в БД,
	 * устанавливает переменную except в случае если пользователь существует
	 */
	private function checkEmailExist()
	{
		$sql="select id from users where email='$this->mail'";
		$this->db->query($sql);
		if(is_array($this->db->fetch_one()))
			$this->except["error_user_exist"] = True;
		
	}
	
	/**
	 * проверяет введенные данные пользователя
	 */
	private function checkUserData()
	{
		$valid_mail="^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";
		$this->checker("mail", $this->mail, $valid_mail);
		$this->checker("password", $this->newpassword, "^.{3,}");
		
	}
	
	/**
	 * проверяет введенные данные нового пользователя
	 */
	private function checkUserRegisterData()
	{
		$this->checker("password", $this->newpassword, $this->newpassword2);
		$this->checker("last_name", $this->last_name);
		$this->checker("first_name", $this->first_name);
	}
	/**
	 * проверяет правиьлность введенных данных адреса доставки
	 * 
	 */
	private function checkAddress()
	{
		$this->checker("region", $this->region);
		$this->checker("house", $this->house, "^\w+?");
		$this->checker("housing", $this->housing);
		$this->checker("city", $this->city);
		$this->checker("street", $this->street);
		$this->checker("apartment", $this->apartment);
		$this->checker("phone", $this->phone, "^(\d+?|\+\d+?)\s?\(?\d{3}?\)?\s?\d+[0-9\-]{3,10}");
		$this->checker("mobile", $this->mobile, "^\(\d{3}\)\s?\d{3}-\d{2}-\d{2}");
	}
	
	
	
	/**
	 * проверяет правиьлность введенных данных юридического лица
	 * 
	 */
	private function checkYur()
	{
			$this->person = True;
			$this->variable['person']= 1;
			$this->checker("organization", $this->organization);
			$this->checker("bank", $this->bank);
			$this->checker("rs", $this->rs, "^\d+");
			$this->checker("inn", $this->inn, "^\d+");
			$this->checker("ks", $this->ks, "^\d+");
			$this->checker("bik", $this->bik, "^\d+");
			$this->checker("kpp", $this->kpp, "^\d+");
			$this->checker("jur_addr", $this->jur_addr);
	}
	
	/**
	 * во время регистрации возвращает ID поледней записи, добавляет в сессию и 
	 * устанавливает приватную переменную id
	 */
	private function getRegisterUserId()
	{
		$id=$this->db->getId("users");
		$this->id = $id;
		$_SESSION["user_id"]=$this->id;
	}
	
	/**
	 * вносит нового пользователя в базу
	 */
	private function registerNewUser()
	{
		$sql = "insert into users(email, password, first_name, midle_name, last_name, jur) 
				values(".sprintf("'%s', '%s', '%s', '%s', '%s', %s", $this->mail, 
				$this->newpassword, $this->first_name,$this->middle_name,
				$this->last_name, $this->db->checkBool($this->person)).")";
		$this->db->query($sql);
		$this->getRegisterUserId();
	}
	
	/**
	 * носит в базу данные о юридическом лице
	 */
	private function registerJurUser()
	{
		$sql = "insert into user_jur(organization, bank, jur_addr, rs, ks, bik, inn, kpp, okdp, okpo, okonh, okved, www)
							values('$this->organization', '$this->bank', '$this->jur_address', 
							$this->rs, $this->ks, $this->bik, $this->inn, $this->kpp, 
							".sprintf("%d, %d, %d, %d,",$this->okdp, $this->okpo, $this->okonh, $this->okved) ."'$this->www')";
		$this->db->query($sql);
		$id=$this->db->getId("user_jur", "_company_id_seq");
		$sql = "insert into users_has_user_jur (id, company_id) values($this->id, $id)";
		$this->db->query($sql);
	}
	
	/**
	 * вносит в БД новый адрес доставки
	 */
	public function addDeliveruAddress()
	{
		$phone="/(\(|\)|\s+?|-|\+)/i";
		$this->phone = preg_replace($phone, "", $this->phone);
		$this->mobile = preg_replace($phone, "", $this->mobile);
		$this->fax = preg_replace($phone, "", $this->fax);
		$this->fax = ($this->fax)?$this->fax:'0';
		
		#FIXME: после формирования базы регионов заменить region_id=1 на $this-region
		$sql="insert into user_address (user_id, region_id, city, 
								metro, street, house, housing, build, office, 
								porch, floor, intercom, apartment, phone, mobile, fax, 
								lift, maillist, \"default\") values 
								($this->id, 1, '$this->city', '$this->metro', '$this->street', 
								'$this->house', '$this->housing', '$this->build', '$this->office', 
								'$this->porch', '$this->floor', '$this->intercom', '$this->apartament', $this->phone, 
								$this->mobile, $this->fax, 
								".$this->db->checkBool($this->lift).", ".$this->db->checkBool($this->maillist).", True)";
		$this->db->query($sql);
	}
}
