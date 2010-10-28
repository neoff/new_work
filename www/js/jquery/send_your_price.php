<?php 
global $user, $GlobalConfig;

if ($_REQUEST['warecode'])
{	
   if (!$_REQUEST['email'] || !$_REQUEST['phone']) $note.="Укажите e-mail или номер телефона<br>";
   if (!$_REQUEST['name']) 							$note.="Укажите Ф.И.О.<br>";
   if (!(int)$_REQUEST['yourprice']) 				$note.="Предложите Вашу цену<br>";
   if (!$_REQUEST['url']) 								$note.="Укажите ссылку на сайт с примером более низкой цены<br>";
   if (empty($_REQUEST['code']) || $_REQUEST['code']!=$_SESSION["security_code"]) $note .= "Введенный вами код не совпадает с кодом указанным на картинке<br>";

   if (!$note)
   {
      $db = new DB_Mvideo;
     
      $sql = "INSERT INTO yourprice
                    (region_id, warecode, email, phone, name, yourprice, url, bonus_card, start_time) 
                    VALUES
                    (".(int)$GlobalConfig['RegionID'].", 
                     ".(int)$_REQUEST['warecode'].",
                    '".addslashes($_REQUEST['email'])."', 
                    '".addslashes($_REQUEST['phone'])."', 
                    '".addslashes($_REQUEST['name'])."', 
                    ".(int)$_REQUEST["yourprice"].",
                    '".addslashes($_REQUEST['url'])."',
                    '".addslashes($_REQUEST['bonus_card'])."',
                    NOW()
                    )";
      //echo $sql;
      $db->query($sql);      
      $note = "Ваша заявка принята";
   }   
   header('Content-Type: text/html; charset=windows-1251');
   echo $note;
}
?>