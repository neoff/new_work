<?php
global $GlobalConfig, $tpl;

$db = new DB_Mvideo;
$page = (int)$_REQUEST['page'];
$type = (int)$_REQUEST['type'];
$segment_name = $_REQUEST['segment_name'];
$count = 0;

if ($type==1) {
			
		$limit = 6;
		$offset = ($page-1)*$limit;
				
		$sql = "
			  SELECT count(w.warecode) as cnt
			  FROM segment_cache
			  JOIN warez_".$GlobalConfig['RegionID']." AS w ON w.warecode = segment_cache.warecode
			  WHERE segment_cache.segment_name = '".$segment_name."'
			  AND segment_cache.region_id=".$GlobalConfig['RegionID']."
			";
		
		$res = $db->query($sql);
	   if ($row=@mysql_fetch_assoc($db->Query_ID)) {
			$count = $row['cnt'];
		}   
  	
  		$sql = "
        SELECT DISTINCT
	       w.warecode,
		    w.FullName,
		    m.MarkName,
		    w.InetQty,
		    Discounted,
	       InetDiscounted,
	       OldPrice,
	       important
		  FROM segment_cache
		  JOIN warez_".$GlobalConfig['RegionID']." AS w ON w.warecode = segment_cache.warecode
		  JOIN marks AS m ON m.MarkID = w.mark
		  WHERE segment_cache.segment_name = '".$segment_name."'
		  AND segment_cache.region_id=".$GlobalConfig['RegionID']."
        ORDER BY important DESC, InetQty DESC
        LIMIT 6
		  OFFSET ".$offset."
	  ";		
}
else {	
		$limit = 4;
		$offset = ($page-1)*$limit;
		
				
		$sql = "
			  SELECT count(w.warecode) as cnt
			  FROM segment_cache
			  JOIN warez_".$GlobalConfig['RegionID']." AS w ON w.warecode = segment_cache.warecode
			  WHERE segment_cache.segment_name = '".$segment_name."_aks'
			  AND segment_cache.region_id=".$GlobalConfig['RegionID']."
			";
		
		$res = $db->query($sql);
	   if ($row=@mysql_fetch_assoc($db->Query_ID)) {
			$count = $row['cnt'];
		}   
  	
  		$sql = "
        SELECT DISTINCT
	       w.warecode,
		    w.FullName,
		    m.MarkName,
		    w.InetQty,
		    Discounted,
	       InetDiscounted,
	       OldPrice,
	       important
		  FROM segment_cache
		  JOIN warez_".$GlobalConfig['RegionID']." AS w ON w.warecode = segment_cache.warecode
		  JOIN marks AS m ON m.MarkID = w.mark
		  WHERE segment_cache.segment_name = '".$segment_name."_aks'
		  AND segment_cache.region_id=".$GlobalConfig['RegionID']."
        ORDER BY important DESC, InetQty DESC
        LIMIT 4
		  OFFSET ".$offset."
	  ";   
}

	$db->query($sql);
	   $num = 1;
		while ($row = @mysql_fetch_assoc($db->Query_ID)) {		
		  
		  if ($row["MarkName"] && $row["FullName"]) {
          list($short_name, $model) = explode($row['MarkName'], $row['FullName']);
        }
        $row = array_merge($row, array(
        		'ImageURL'  => toLatinUrl($row['FullName']),
        		'ModelName'	=> $model,
        		'ShortName'	=> $short_name,
        		'tr' 		   => $num % 2,
        ));
        
        ++$num;
		  
		  $main_warez[$row['warecode']] = $row;
		  $ids[] = $row['warecode'];
		}  

if (count($ids) > 0) {	
		  // параметры
		  $sql = "
		    SELECT
		      dl.warecode,
		      dl.PrName,
		      dl.PrVal
			FROM descriptionlist AS dl	
			WHERE dl.warecode IN (".implode(',',$ids).")  AND ShortDescr=1  AND PropertyCode NOT IN (34,36,12952)
			ORDER BY PropertyOrder
		  ";
		  //echo $sql;
		  $db->query($sql);
		  while ($row = @mysql_fetch_assoc($db->Query_ID)) {		    	    
		    if (count($main_warez[$row['warecode']]['opts'])>2) {
		      continue;
		    }		  
		    $main_warez[$row['warecode']]['opts'][] = $row;
		  }	
		}
	
if (count($main_warez)) {		
	
	header("Content-Type:text/html;charset=\"windows-1251\"");	
	$tpl->assign("main_warez", $main_warez);	
	echo $tpl->fetch("adv-txt/list/bts_warez_list.tpl");
}
?>