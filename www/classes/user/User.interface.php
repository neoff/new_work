<?php
/**  
 * шаблон, интерфейс для выдачи информации в личном кабинете
 * 
 * @package    User
 * @subpackage privateOffice
 * @since      29.10.2010 15:25:17
 * @author     enesterov
 * @category   interface
 */

	namespace User;
	require_once 'classes/abstract/module.abstract.class.php';
	
interface userTemplates {
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

interface userRegistration {	
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

abstract class userTemplate extends \module_abstract implements userTemplates {
	private $tpl_name;
	private $user_id;
	
	public function setTemplate($template)
	{
		$this->tpl_name = $template;
	}
	public function getTemplate()
	{
		$this->template->add_template("header");
		$this->template->set_global(array("site" => $this->this_site),"header");
		$header = $this->template->parse("header");
		
		$this->template->add_template("footer");
		$footer = $this->template->parse("footer");
		
		$this->template->add_template("user/login_form");
		$login = $this->template->parse("user/login_form");
		$this->template->context('row');
		$this->template->add_template("main");
		$this->template->set_global(array("header"=>$header,
						"footer"=>$footer,
						"site"=>$this->this_site, 
						"content"=>$this->getForm()),"main");
		return $this->template->parse("main");
	}
	
	public function getForm($pikup=False){}
}