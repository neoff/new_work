<?php
/**  
 * 
 * 
 * @package    
 * @subpackage 
 * @since      01.11.2010 17:56:25
 * @author     enesterov
 * @category   none
 */

class MyException extends Exception {
   public function __construct($message, $code, $filename, $lineno) {
      parent::__construct($message, $code);
      
      $this->file = $filename;
      $this->line = $lineno;
   }
}



function myErrorHandler($errno, $msg, $file, $line) {

	//Если ошибки откл-ничего не делаем. Иначе выводим
	if (error_reporting() == 0) return;
	$E_MESSAGE = new MyException($msg, $errno, $file, $line);
	echo '<div id="E_ERROR" style="background-color:#ffffb3;z-index:90000;position:absolute;">';
	echo "Произошла ошибка:<b>$errno</b>!<br/>"; 
	echo "Файл: <b>$file</b>, строка <b style='color:#F00;font-weight:bold;'>$line</b>.<br/>";  
	echo "Текст ошибки: <b>$msg</b><br/><br/>";
	$E_MESSAGE = str_replace("\n", "<br/>", $E_MESSAGE);
	echo "$E_MESSAGE";
	echo "</div>";

}

set_error_handler("myErrorHandler", EXCEPTION_FLAG);
//set_error_handler(create_function('$c, $m, $f, $l', 'throw new MyException($m, $c, $f, $l);'), E_ALL);