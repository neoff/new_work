function display_submenu(id)
      {
         var nodes = document.getElementsByTagName('DIV');
         for (var i = 0; i < nodes.length; i++)
         {
            if (nodes[i].id.substr(0, 3) == "fd_")
            {
               nodes[i].style.display='none';
            }
            if (nodes[i].id.substr(0, 4) == "fdm_")
            {
               nodes[i].className = "n-act";
            }
            if (nodes[i].id.substr(0, 5) == "fdm_4")
            {
               nodes[i].className = "n-act-aks";
            }
         }
         if (document.getElementById('pdo_td'))
         {
	         if (id==0 || id==1 || id==3)
	         {
	         	if (isIE==true) {display = 'inline-block';}
	         	else				 {display = 'table-cell';}
	         }
	         else
	         {
	         	display = 'none';
	         }
	         document.getElementById('pdo_td').style.display = display;
         }
         if(document.getElementById('fd_'+id))  document.getElementById('fd_'+id).style.display='block';
         if(document.getElementById('fdm_'+id)) document.getElementById('fdm_'+id).className = "act";
      }

function good_review(good,rid)
{           
            $.post('/newdesign/js/jquery/good_review.php', 
        			{'good': good,
                	 'rid': rid}, 
        			function(data)
        			{
                		 document.getElementById('good_review'+rid).innerHTML = 'Спасибо. Ваш голос учтен.';
        			});

}


         
function add_cert(type, warecode)
{
	var link = '';
	var cb = document.getElementsByTagName('input');
	for (var r = 0; r < cb.length; r++)
	{
	  
		if (type=='cert')
		{
		  if (cb[r].name.substr(0, 13) == 'install_kupon' && cb[r].checked)
		  {
			  link += '&kupon['+warecode+']['+cb[r].id+']=1';
		  }
		  else if (cb[r].name.substr(0, 4) == 'cert' && cb[r].checked)
		  {
			 var cert_id = cb[r].id.substr(5, cb[r].id.length);
		     link += '&cert['+warecode+']['+cert_id+']=1';
		  }
		}
		else if (type=='aks')
		{
			if (cb[r].name.substr(0, 3) == 'aks' && cb[r].checked)
			{
				var aks_id = cb[r].id.substr(3, cb[r].id.length);
			   link += '&ids['+aks_id+']=1';
			}
		}
	}
	if (warecode)
	{
		link += '&ids['+warecode+']=1';
	}
	if (link) 
	{
		document.location.href = '/homeshop/?p=cart&ref=new_card' + link;
	}
}



function CountBack(secs, div_id, deal) {

  var type_deal = (deal ? deal : 0);
  var seconds = secs;
	
  if (secs < 0) {
    if (document.location.search)
    {
       var add_chr = '&';
    }
    else
    {
       var add_chr = '?';
    }
    document.location.href= document.location.href + add_chr + 'rnd=1';
    return;
  }
 
  
  // days
  var days  = Math.ceil(secs / 86400) - 1;
  secs -= days * 86400;
  
  
  // hours
  var hours = Math.ceil(secs / 3600) - 1;
  secs -= hours * 3600;
  
  // minutes
  var mins  = Math.ceil(secs / 60) - 1;
  secs -= mins * 60;
  
  
  var d_name = 'дней';
  if (days == 1) {
  	d_name = 'день';
  }
  if (days > 1 && days < 5) {
  	d_name = 'дня';
  }
  
  
  if (days > 0) {
  	secs  = '';
  	//days = '<span>'+days+'</span>&nbsp;'+d_name;
  } else {
  	days = '';
  	//secs = '<span>'+secs+'</span>&nbsp;сек.';
  }
  
  if (hours > 0) {
  	//hours = '<span>'+hours+'</span>&nbsp;час.';
  } else {
  	hours = '';
  }
  
  if (mins > 0) {
  	//mins = '<span>'+mins+'</span>&nbsp;мин.';
  } else {
  	mins = '';
  }
  
  
  //document.getElementById(div_id).innerHTML = days+hours+mins+secs;
    
  
  if (days < 10) {
	  days = '0'+days;
	  }
  if (hours < 10) {
	  	hours = '0'+hours;
	  }
	  
  if (mins < 10) {
	  	mins = '0'+mins;
	  } 
  
  if (secs < 10) {
	  	secs = '0'+secs;
	  } 
  
  
  if (deal) {
	  document.getElementById(div_id).innerHTML = '<b>'+hours+'</b> час. <b>'+mins+'</b> мин. <b>'+secs+'</b> сек. ';
  } else {
	  document.getElementById(div_id).innerHTML = '<table width="100%" cellpadding="0" cellspacing="0"><tr>'+
		  									(days>0 ?  '<td>'+days +'</td><td class="time_line">|</td>' : '')+
		  									(hours>0 ? '<td>'+hours+'</td><td class="time_line">|</td>' : '')+
		  									(mins>0 ?  '<td>'+mins +'</td>' : '')+
		  									(secs>0 ?  '<td class="time_line">|</td><td>'+secs +'</td>' : '')+
		  									'</tr><tr class="s_text">'+
		  									(days>0 ?  '<td>'+human_plural_form(0, days,  new Array('день', 'дня', 'дней')) +'</td><td class="time_line"></td>' : '')+
		  									(hours>0 ? '<td>'+human_plural_form(0, hours, new Array('час', 'часа', 'часов')) +'</td><td class="time_line"></td>' : '')+
		  									(mins>0 ?  '<td>'+human_plural_form(0, mins,  new Array('минута', 'минуты', 'минут')) +'</td>' : '')+
		  									(secs>0 ?  '<td class="time_line"></td><td>секунд</td>' : '')+	  									
		  									'</tr></table>';
  }
	  
  
  
  
  if (CountActive) setTimeout("CountBack("+(seconds+CountStepper)+", '"+ div_id+"', "+type_deal+")", SetTimeOutPeriod);
}



/**
* @param $number int число чего-либо
* @param $titles array варинаты написания для количества 1, 2 и 5 
* @return string
*/
function human_plural_form(id, number, titles){
    
	var cases = new Array (2, 0, 1, 1, 1, 2);
	var value = titles[(number%100>4 && number%100<20) ? 2 : cases[Math.min(number%10, 5)]];
	if (id) {		
		document.getElementById(id).innerHTML = value;
	} else {
		return value;
	}
}