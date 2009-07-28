<? 

// Settings
$jobhight = 20;		// Pixel height of each job
$jobspacing = 4;	// Pixel vertical spacing between jobs
$minhight = 3;		// Height unit of the timeline bar (minheight is 3 jobs vertical) 
$day = 0;

$range = 1;


if (isset($_GET['day'])) {
	$day = ($_GET['day'] + 0);
}
if (isset($_GET['range'])) {
	$range = ($_GET['range'] + 0);
	if($range < 0) 
		$range = 0;
}
$rangestart =  (86400 * $range) + 43200;
$rangewidth = ($rangestart * 2) / 100;
$rangedays = ($range*2 + 1);
$rangeslice = 100 / $rangedays;


$jobtypes = DBFindMany("JobType","from jobtype");
$jobs = DBFindMany("Job","from job where
	userid=? and 
	(status='cancelled' or status='complete' or status='active' or status='scheduled') and 
	deleted = 0 and 
	(
		DATE_SUB(CURDATE(),INTERVAL ? DAY) <= enddate and 
		DATE_SUB(CURDATE(),INTERVAL ? DAY) >= startdate
	) order by startdate",false,array($USER->id,($range - $day),(-$day - $range)));

$time = date("G");
$timeposition = $rangeslice*$range + ($time/24)*$rangeslice - ($rangeslice * $day);

$content = "";
$numberofjobs = count($jobs);
$i = 0;

$centerposition = strtotime(date("m/j/Y") . "12:00") + (86400 * $day);

$placments = array();
$jobcount = count($jobs);

$jobcolor = array("scheduled" => "#3399FF","active" => "#D0AA0D","complete" => "#00FF00","cancelled" => "#00FF00");
$jobids = array();
$jobstatus = array();


foreach($jobs as $job) {
	$startlocation =  (strtotime($job->startdate) - ($centerposition - $rangestart));
	$startlocation += date("H", strtotime($job->starttime)) * 3600;
	$startlocation += date("i", strtotime($job->starttime)) * 60;

	$endlocation =  (strtotime($job->enddate) - ($centerposition - $rangestart));
	$endlocation += date("H", strtotime($job->endtime)) * 3600;
	$endlocation += date("i", strtotime($job->endtime)) * 60;
	$extracontent = "";
	$leftborder = true; 

	if($startlocation < 0) {
		$startday = 0;
		$startlocation = -2;
		$leftborder = false; 
		$width = ($endlocation / $rangewidth) + 2;
		$extracontent .= '<img style="position:absolute;border:0px;top: 0px;left: 0px" src="img/timeline/_' . $job->status . 'left' . $jobtypes[$job->jobtypeid]->systempriority . '.gif" alt=""/>';
	} else {
		$startday = floor($startlocation/86400);
		$width = ($endlocation - $startlocation) / $rangewidth;
		$startlocation /= $rangewidth;
	}

	$endday = floor($endlocation/86400);
	
	$j = 0;	
	while($j < $jobcount){
		if(!isset($placment[$startday][$j])){
			$placment[$startday][$j] = $i;
			if($startday != $endday){
				$d = $startday + 1;
				while($d <= $rangedays && $d <= $endday) {
					$placment[$d][$j] = $i;	
					$d++;
				}
				if($endday >= $rangedays) {
					$width = 102 - $startlocation;
					$extracontent .= '<img style="position:absolute;border:0px;top: 0px;right: 0px" src="img/timeline/_' . $job->status .  'right' . $jobtypes[$job->jobtypeid]->systempriority . '.gif" alt=""/>';
				}
			}	
			
			//if($width < 2) // Set a minimum width for visablility
			//	$width = 2;
			$extracontent .= '<img style="position:absolute;border:0px;top: 0px;right: 0px" src="img/timeline/_' . $job->status . 'rightspecial' . $jobtypes[$job->jobtypeid]->systempriority . '.gif" alt=""/>';
			
				
			//$content .= '<a class="job" id="_' . $i . '" href="job.php?id=' . $job->id. '">';
			
			$jobids[$i] = "'$job->id'";
			$jobstatus[$i] = "'" . ucfirst($job->status) . "'";
			
			//$content .= "<div id="__$i" class="" . $job->status . "job" style="left: " . $startlocation . "%;top: " . (($j*$jobhight) + ($j*$jobspacing)) . "px;height: " . $jobhight . "px;width: " . $width . "%;" . (($endday >= $rangedays)?"border-right:0px;":";") . ((!$leftborder)?"border-left:0px;":";") . "">\n";				
			//$content .= "<div id="__$i" class="" . $job->status . "job" style="background: url(img/timeline/_" . $job->status . $jobtypes[$job->jobtypeid]->systempriority .  ".gif);left: " . $startlocation . "%;top: " . (($j*$jobhight) + ($j*$jobspacing)) . "px;height: " . $jobhight . "px;width: " . $width . "%;" . (($endday >= $rangedays)?"border-right:0px;":";") . ((!$leftborder)?"border-left:0px;":";") . "">\n";				
			
			$content .= '<div id="__' . $i. '" class="jobcontainer" style="left: ' . $startlocation . '%;top: ' . (($j*$jobhight) + ($j*$jobspacing)) . 'px;height: ' . $jobhight . 'px;width: ' . $width . '%;">';				
			$content .= '<div id="box_' . $i . '" class="box">';
				$content .= $job->name;
			//$content .= "\n<br /><hr>" . $jobtypes[$job->jobtypeid]->name . "\n<br /><hr>";
			//$content .=  "Start Time: $job->startdate $job->starttime\n<br />End Time: $job->enddate $job->endtime\n\n";
			$content .= "</div>";			
			
			$content .= '<div class="jobbar" style="left: -1px;background-color: ' . (isset($jobcolor[$job->status])?$jobcolor[$job->status]:"#000") . ';">&nbsp;</div>';				
			
	
			$content .=  "</div>";//</a>\n";
			
		//	$content .=  "$extracontent</div></a>\n";
			/*
			
			new Tip(e, formdata[itemname].fieldhelp, {
					title: formdata[itemname].label,
					style: 'protogrey',
					stem: 'bottomLeft',
					hook: { tip: 'bottomLeft', mouse: true },
					offset: { x: 14, y: 0 }
				});
				
			Event.observe(window, 'load', function() {
				new Tip('shortcutmenu', $('shortcuts'), {
					style: 'default',
					radius: 4,
					border: 4,
					target: 'shortcutmenu',
					hideOn: false,
					hideAfter: 0.5,
					hook: { target: 'bottomRight', tip: 'topRight' },
					offset: { x: 6, y: 0 },
					width: 'auto'
				});
			});
			*/
			$i++;
			if($minhight <= $j )
				$minhight = $j + 1;
			
			
			break;	
		}			
		$j++;
	}
	
}


$minhight = $minhight * $jobhight + $minhight*$jobspacing ;

?>
<div style="height: 180px;overflow: auto">
	<div id="maincanvas"  style="height:<? echo $minhight ?>px;">
	
	<table>
		<tr>
			<td width="80px">
			<a id="_backward" href="start.php?day=<? echo ($day - 1) . "&range=$range" ?>">
				<img style"align: right;" src="img/timeline/arrowleft.gif"  alt="Backward" border="0"/>
			</a>
			</td>
			
			<td width="100%">&nbsp;
				
		<div id="canvas" style="height:<? echo $minhight ?>px;"> 
			<img class="left" src="img/timeline/canvasleft.gif"  alt=""  width="2%" height="100%"/>
			<img class="right" src="img/timeline/canvasright.gif" alt="" width="2%" height="100%"/>
		
			<div class="daylineend" style="left: 0%;"></div>
			
			<?
				for($k=1;$k < $rangedays;$k++){
					echo '<div class="dayline" style="left: ' . ($rangeslice * $k) . '%;"></div>';
					echo '<div class="daylinetop" style="left: ' . ($rangeslice * $k) . '%;"></div>';
					echo '<div class="daylinebottom" style="left: ' . ($rangeslice * $k) . '%;"></div>';
				}
			 ?>
			<div class="daylineend" style="left: 100%;"></div>
		
		<? if(0 && $day>=-$range && $day<=$range) { ?>
			<div id="now" style="left: <? echo $timeposition ?>%;"></div>
			<div id="nowtop" style="left: <? echo $timeposition ?>%;"></div>	
			<div id="nowbottom" style="left: <? echo $timeposition ?>%;"></div>
			
			<div id="nowlabel" style="left: <? echo $timeposition - 10 ?>%;"><?=date("h:i a ")?></div>
		<? } 
		
		$labelstart = ($centerposition - $rangestart) + 43200;
		$today = date("M jS");
		for($k=0;$k < $rangedays;$k++){
			$daylabel = date("M jS", $labelstart + 86400 * $k);
			if($daylabel==$today) {
				echo '<div class="daylabeltoday" style="left: ' . ($rangeslice * $k) . '%;width: ' . $rangeslice . '%;">Today<br />(' . $daylabel . ')</div>';	
			} else
				echo '<div class="daylabel" style="left: ' . ($rangeslice * $k) . '%;width: ' . $rangeslice . '%;">' . $daylabel . '</div>';
		}
		echo $content;
		?>
		
		</div>
		
			</td>
			<td width="80px">	
				<a id="_forward" href="start.php?day=<? echo ($day + 1) . "&range=$range" ?>">
					<img  src="img/timeline/arrowright.gif"  alt="Forward" border="0"/>
				</a>
			</td>
		</tr>
		</table>
	</div>
</div>

<script>
	var jobids=new Array(<?= implode(",",$jobids) ?>);
	var jobstatus=new Array(<?= implode(",",$jobstatus) ?>);

	$('canvas').blindDown({duration: 0.4});
	var cw = $('canvas').getWidth();
	if(cw) { <!-- IE can not handle getwidth in some locations -->
		for(i=0;i<<? echo $i ?>;i++){
			var w = $('__' + i).getWidth();
			if(!w) {break;}
			$('__' + i).style.width = "0%";
			
			$('__' + i).morph('width:' + ((w/cw)*100) + '%;',{duration: 1.5});
			$('__' + i).tip = new Tip('__' + i, getcontent(i), {
				style: 'protogrey',
				radius: 4,
				border: 4,
				showOn: 'click',
				hideOn: false,
				hideAfter: 0.5,
				stem: 'topRight',
				hook: {  target: 'bottomMiddle', tip: 'topRight'  },
				width: '220px',
				offset: { x: 0, y: -5 }
			});
			$('__' + i).observe('prototip:shown', function() {
				//	this.tip.wrapper.shake();
				//Effect.BlindDown(this.tip.wrapper, { duration: 0.3 });
				//this.pulsate({ pulses: 1, duration: 0.5 });
			});



		}
	}
	function getcontent(id) {
		var content = $("box_" + id).innerHTML;
		content += '<br /><a href="job.php?id=' + jobids[id] + '">Edit</a><br />';
		if(jobstatus[id] == "Active")
			content += '<img width="200px" src="graph_job.png.php?jobid=' + jobids[id] + '&junk=" />';		
		return content;
	}
	
	
	
</script>
