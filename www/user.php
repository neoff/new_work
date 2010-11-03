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
	session_start();
	

$a= new Registration();
$a->fetch();

