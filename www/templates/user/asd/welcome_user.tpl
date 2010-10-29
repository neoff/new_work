{if $user->userdata.user_id && $user->userdata.if_const}
{assign var="forward" value="error"|SetParam:""}


<div class="flr">
			<b class="cart_buttons" id="grey_left"></b>
			<b class="cart_buttons" id="grey_bg">	
				<b class="fll"><a href="{#UserPage#}?logout=1&forward={$forward|escape:"url"}" style="color:#FFF">Выход</a></b>
			</b>
			<b class="cart_buttons" id="grey_right"></b>
</div> 
<div class="clear"></div>
{/if}