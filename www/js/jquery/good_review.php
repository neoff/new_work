<?php

$good = $_REQUEST['good'];
$rid = $_REQUEST['rid'];
if ($rid)
{
  $db = new DB_Mvideo();
  $db->query("update reviews_new set ".($good==1 ? "rew_good=rew_good+1, " : null)."rew_total=rew_total+1 where rew_id=".(int)$rid);
  // ставим куку
  setcookie("mvrew_".$rid,1,time()+60*60*24*30,"/"); //30 дней
}
$html = "Спасибо. Ваш голос учтен.";

$GLOBALS['_RESULT'] = array(
'html' =>  $html
);
echo $html;
?>
