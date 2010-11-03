<?php
/**  
 * шаблон, интерфейс для получения информации при регистрации и в личном кабинете
 * 
 * @package    User
 * @subpackage privateOffice
 * @since      29.10.2010 15:25:17
 * @author     enesterov
 * @category   interface
 */

	namespace User;
	require_once 'classes/abstract/module.abstract.class.php';
/**
 * интерфейс шаблонов для страниц
 * @author enesterov
 *
 */	
interface UserTemplates {
	
	/**
	 * устанавливаем имя шаблона
	 * @param $template - название шаблона
	 */
	public function setTemplate($template);
	
	/**
	 * возвращает собраный шаблон с хедером и футером
	 */
	public function getTemplate();
	
	
	/**
	 * возвращает чистый шаблон, без хедера и футера
	 * @param $picup - true в случае если нужно забрать 
	 * сокращенную форму, без адреса доставки
	 */
	public function getForm($pikup=False);
}

/**
 * шаблон для регистрации пользователя
 * @author enesterov
 *
 */
interface UserRegistration {
	/**
	 * проверяет данные пост запроса, авторизация или регистрация
	 */
	public function checkPost();
	/**
	 * проверяет логин и пароль пользователя, регистрирует данные в сесии
	 */
	public function authUser();
	
	/**
	 * регистрирует нового пользователя, в зависимости от параметров
	 * регистрирует физическое или юридическое лицо
	 */
	public function addUser();

	/**
	 * отсылает забытый пароль на почту пользователя
	 * 
	 */
	public function forgetPassword();
}

 /**
 * шаблон для личного кабинета
 * @author enesterov
 *
 */
interface UserPrivateOffice{
	
	/**
	 * возвращает ФИО пользователя, телефон (контактный, мобильный)
	 * для юридических лиц ИНН, БИК, Кор. счет + юридический адресс
	 */
	public function getUserInfo();
	
	/**
	 * возвращает адрес доставки "по умолчанию"
	 */
	public function getDeliveryAddress();
	
	/**
	 * возвращает массив адресов пользователя
	 */
	public function getUserAdress();
}

/**
 * абстракция для выводит на сайт  на сайт
 * @author enesterov
 *
 */
abstract class UserTemplate extends \module_abstract implements UserTemplates {
	private $tpl_name;
	private $id;
	
	/**
	 * @see User.userTemplates::setTemplate()
	 */
	public function setTemplate($template)
	{
		$this->tpl_name = $template;
	}
	
	/**
	 * @see User.userTemplates::getTemplate()
	 */
	public function getTemplate()
	{
		$this->template->add_template("header");
		$this->template->set_global(array("site" => $this->this_site),"header");
		$header = $this->template->parse("header");
		
		$this->template->add_template("footer");
		$footer = $this->template->parse("footer");
		
		
		
		$this->template->add_template("main");
		$this->template->set_global(array("header"=>$header,
						"footer"=>$footer,
						"site"=>$this->this_site, 
						"content"=>$this->getForm()),"main");
		return $this->template->parse("main");
	}
	
	/**
	 * выводит на шаблон регистрации с header & footer
	 * проверяет пост запросы
	 * основная ф-я для входа/регистрации пользователя
	 * @see module_abstract::fetch()
	 */
	public function fetch()
	{
		if($_POST)
			$this->checkPost();
			
		echo $this->getTemplate();
	}
	
	public function getForm($pikup=False){}
}