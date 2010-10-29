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
	require_once 'privateOffice.interface.php';
	
class User extends Users {
	
	public function __construct() {
		parent::__construct();
	}
	/**
	 * возвращает ФИО пользователя, телефон (контактный, мобильный)
	 * для юридических лиц ИНН, БИК, Кор. счет + юридический адресс
	 */
	public function getUserInfo(){}
	
	/**
	 * принемает $_POST запрос с данными
	 * возвращает ФИО пользователя, телефон (контактный, мобильный)
	 * для юридических лиц ИНН, БИК, Кор. счет + юридический адресс
	 */
	public function editUserInfo(){}
	
	/**
	 * возвращает адрес доставки "по умолчанию"
	 */
	public function getDeliveryAddress(){}
	
	/**
	 * возвращает все адреса пользователя
	 */
	public function getUserAdress(){}
	
	/**
	 * принемает $_POST запрос с данными
	 * возвращает все адрес пользователя
	 */
	public function addUserAdress(){}
	
	/**
	 * принемает $_POST запрос с данными
	 * возвращает все адрес пользователя
	 */
	public function editUserAdress(){}
	
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
	
	public function fetch();
	
}