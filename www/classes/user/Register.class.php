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


class Registration extends userTemplate implements userRegistration {
	public function __construct($template) {
		$this->tpl_name = "user/user_form";
		parent::__construct();
		$this->template->add_template($this->tpl_name);
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
	
	public function authUser()
	{
		
	}
	
	public function addUser()
	{
		if($_POST)
		{
			echo "POST!!!";
		}
	}
	
	public function getForm($pikup=False)
	{
		$this->template->set_global(array("site"=>$this->this_site),$this->tpl_name);
		$this->template->set_block("javascript", "", $this->tpl_name);
		return $this->template->parse($this->tpl_name);
	}
	
	public function forgetPassword(){}
	
	public function fetch()
	{
		echo $this->getTemplate();
	}
	
	
}
