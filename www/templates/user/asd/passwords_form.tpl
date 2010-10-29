<table border=0 cellspacing=0 cellpadding=0>
<tr>
  <td align=right>пароль:</td>
  <td align=right>&nbsp;&nbsp;<input type=password name='userdata[newpassword]' maxlength=30 value='{$password|default:$user->userdata.expassword|escape}'></td>
</tr>
<tr>
  <td align=right>повторите<br>пароль:</td>
  <td align=right>&nbsp;&nbsp;<input type=password name='userdata[newpassword2]'   maxlength=30 value='{$password|default:$user->userdata.expassword|escape}'>
  </td>
</tr>
</table>