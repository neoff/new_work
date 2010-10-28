<?php
/**  
 * 
 * 
 * @package    user
 * @subpackage  none
 * @since      26.10.2010 18:37:01
 * @author     enesterov
 * @category   none
 */
	namespace User;
	require_once 'classes/abstract/module.abstract.class.php';
	
	$start = microtime(true);
	
	date_default_timezone_set('Europe/Moscow');
	ini_set("display_errors",'Off');
	ini_set("error_reporting",E_ALL & !E_NOTICE);
	session_start();
	
class User extends \module_abstract{
	
	public function __construct() {
		parent::__construct();
	}
	/**
	 * возвращает ФИО пользователя, телефон (контактный, мобильный)
	 * для юридических лиц ИНН, БИК, Кор. счет + юридический адресс
	 */
	public function getUserInfo(){}
	
	/**
	 * возвращает адрес доставки "по умолчанию"
	 */
	public function getDeliveryAddress(){}
	
	/**
	 * возвращает все адреса пользователя
	 */
	public function getUserAdress(){}
	
}

class Registration extends \module_abstract{
	
	private $user_id;
	
	public function __construct() {
		parent::__construct();
	}
	
	
	/**
	 * регистрирует физическое лицо
	 * 
	 * @return bool;
	 * @param request
	 */
	private function registerFiz(){}
	/**
	 * регистрирует юридическое лицо
	 * 
	 * @return bool;
	 * @param request;
	 */
	private function registerYur(){}
	
	/**
	 * проверяет логин и пароль пользователя, регистрирует данные в сесии
	 */
	public function authUser(){}
	/**
	 * регистрирует нового пользователя, в зависимости от параметров
	 * регистрирует физическое или юридическое лицо
	 */
	public function addUser(){}
	/**
	 * отсылает забытый пароьл на почту пользователя
	 * 
	 */
	public function forgetPassword(){}
}

class Registration extends \module_abstract{
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * возвращает данные из сесии в виде масива
	 */
	public function getSessionData(){}
	/**
	 * станавливает данные в сессию
	 */
	public function setSessionData(){}
}