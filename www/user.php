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
	
	require_once 'classes/user/Register.class.php';
	
	$start = microtime(true);
	
	date_default_timezone_set('Europe/Moscow');
	ini_set("display_errors",'On');
	//ini_set("error_reporting",E_ALL & !E_NOTICE);
	session_start();
	

$a= new Registration("user/user_form");
$a->fetch();
