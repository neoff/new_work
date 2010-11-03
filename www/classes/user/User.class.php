<?php
/**  
 * 
 * 
 * @package    UserOffice
 * @subpackage privatOffice
 * @since      29.10.2010 11:07:04
 * @author     enesterov
 * @category   controller
 */

	namespace User;
	require_once 'User.interface.php';





class User extends UserTemplate implements UserPrivateOffice {
	
	public function __construct() {
		$this->tpl_name = "user/user_form";
		parent::__construct();
		$this->template->add_template($this->tpl_name);
	}
	
	/**
	 * принемает $_POST запрос с данными
	 * возвращает ФИО пользователя, телефон (контактный, мобильный)
	 * для юридических лиц ИНН, БИК, Кор. счет + юридический адресс
	 */
	private function editUserInfo(){}
	
	/**
	 * принемает $_POST запрос с данными
	 * возвращает все адрес пользователя
	 */
	private function addUserAdress(){}
	
	/**
	 * принемает $_POST запрос с данными
	 * возвращает все адрес пользователя
	 */
	private function editUserAdress(){}
	
	/**
	 * возвращает данные из сесии в виде масива
	 */
	public function getSessionData(){}
	
	/**
	 * устанавливает данные в сессию
	 * @param $name
	 * @param $value
	 */
	public function setSessionData($name, $value){}
	
}