<? 

function fmt_timeline_jobs_actions ($obj, $name) {
	$array = jobs_actionlinks($obj);
	$links = is_array($array) ? $array : func_get_args();
	
	foreach ($links as $key => $link)
		if ($link == "")
			unset($links[$key]);
	return implode("&nbsp; &nbsp;",$links); // breaking space in the middle is as designed
}


// Settings
$jobhight = 20;		// Pixel height of each job
$jobspacing = 4;	// Pixel vertical spacing between jobs
$minhight = 3;		// Height unit of the timeline bar (minheight is 3 jobs vertical) 
$day = 0;

$range = 1;

if (isset($_GET['timelineday'])) {
	$_SESSION['timelineday'] = ($_GET['timelineday'] + 0);
}
if (isset($_GET['timelinerange'])) {
	$_SESSION['timelinerange'] = ($_GET['timelinerange']<0)?0:($_GET['timelinerange'] + 0);
}
if (isset($_SESSION['timelineday'])) {
	$day = $_SESSION['timelineday'];
}
if (isset($_SESSION['timelinerange'])) {
	$range = $_SESSION['timelinerange'];
}



$rangestart =  (86400 * $range) + 43200;
$rangewidth = ($rangestart * 2) / 100;
$rangedays = ($range*2 + 1);
$rangeslice = 100 / $rangedays;


$jobtypes = DBFindMany("JobType","from jobtype");
$jobs = DBFindMany("Job","from job where
	userid=? and 
	status in ('scheduled','processing','procactive','active','complete','cancelling','cancelled')
	and 
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
$jobcolor = array("scheduled" => "#0000ff","processing"=>"#00ffff","active" => "#ffff00","complete" => "#00ff00","cancelling" => "#ff5500","cancelled" => "#ff0000");
//$jobcolor = array("scheduled" => "#3399FF","processing"=>"#D0AA0D","active" => "#D0AA0D","complete" => "#00FF00","cancelling" => "#00FF00","cancelled" => "#00FF00");
$jobids = array();
$jobstatus = array();


foreach($jobs as $job) {
	if($job->status == 'procactive') {
		$job->status = 'processing';
	}
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
			$statuscolor = (isset($jobcolor[$job->status])?$jobcolor[$job->status]:"#000");
			
			//$content .= "<div id="__$i" class="" . $job->status . "job" style="left: " . $startlocation . "%;top: " . (($j*$jobhight) + ($j*$jobspacing)) . "px;height: " . $jobhight . "px;width: " . $width . "%;" . (($endday >= $rangedays)?"border-right:0px;":";") . ((!$leftborder)?"border-left:0px;":";") . "">\n";				
			//$content .= "<div id="__$i" class="" . $job->status . "job" style="background: url(img/timeline/_" . $job->status . $jobtypes[$job->jobtypeid]->systempriority .  ".gif);left: " . $startlocation . "%;top: " . (($j*$jobhight) + ($j*$jobspacing)) . "px;height: " . $jobhight . "px;width: " . $width . "%;" . (($endday >= $rangedays)?"border-right:0px;":";") . ((!$leftborder)?"border-left:0px;":";") . "">\n";				
			
			$content .= '<div id="__' . $i. '" class="jobcontainer" style="left: ' . $startlocation . '%;top: ' . (($j*$jobhight) + ($j*$jobspacing)) . 'px;height: ' . $jobhight . 'px;width: ' . $width . '%;">';				
			$content .= '<div class="jobname">';
				$content .= $job->name;
			//$content .= "\n<br /><hr>" . $jobtypes[$job->jobtypeid]->name . "\n<br /><hr>";
			//$content .=  "Start Time: $job->startdate $job->starttime\n<br />End Time: $job->enddate $job->endtime\n\n";
			$content .= "</div>";
			$content .= '<div id="box_' . $i . '" style="display:none"><div class="box">';
				$content .= '<div class="boxname">' . $job->name . '</div>';
				$content .= '<div class="jobbar" style="position: relative;background-color: ' . $statuscolor . ';top:3px;height:auto;">Status:&nbsp;' .  ucfirst($job->status) . '</div>';				
				$content .= '<p id="boxc_' . $i . '" >' . fmt_timeline_jobs_actions($job,"job") . '</p>';
				
				//$content .= '<div style="width: 102%;background-color: ' . $statuscolor . ';left:-10%;height:auto;border:1px;margin:0px;">&nbsp;&nbsp;' .  ucfirst($job->status) . '</div>';				
				
			$content .= "</div></div>";
			
			
			$content .= '<div class="jobbar" style="background-color: ' . $statuscolor . ';">&nbsp;</div>';				
			
	
			$content .=  "</div>";//</a>\n";
			
		//	$content .=  "$extracontent</div></a>\n";
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
	<div id="maincanvas"  style="height:<? echo $minhight + 58 ?>px;">
	
	<table>
		<tr>
			<td width="80px">
			<a id="_backward" href="start.php?timelineday=<?= ($day-1) ?>">
				<img style"align: right;" src="img/timeline/arrowleft.gif"  alt="Backward" border="0"/>
			</a>
			</td>
			
			<td width="100%">&nbsp;
				
		<div id="canvas" style="height:<? echo $minhight ?>px;"> 
			<img class="canvasleft" src="img/timeline/canvasleft.gif"  alt=""  width="2%" height="100%"/>
			<img class="canvasright" src="img/timeline/canvasright.gif" alt="" width="2%" height="100%"/>
		
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
			//$daylabel = str_replace(" ","&nbsp;",date("M jS", $labelstart + 86400 * $k));
			$daylabel = date("M jS", $labelstart + 86400 * $k);
			if($daylabel==$today) {
				$daylabel = str_replace(" ","&nbsp;",$daylabel);
				echo '<div class="daylabeltoday" style="left: ' . ($rangeslice * $k) . '%;width: ' . $rangeslice . '%;">Today<br />(' . $daylabel . ')</div>';	
			} else
				echo '<div class="daylabel" style="left: ' . ($rangeslice * $k) . '%;width: ' . $rangeslice . '%;">' . $daylabel . '</div>';
		}
		echo $content;
		?>
		
		</div>
			</td>
			<td width="80px">	
				<a id="_forward" href="start.php?timelineday=<?= ($day + 1) ?>">
					<img  src="img/timeline/arrowright.gif"  alt="Forward" border="0"/>
				</a>
			</td>
		</tr>
		</table>
		</div>
	</div>

			
			<?=	icon_button(_L('Refresh'),"fugue/arrow_circle_double_135","window.location.reload()",null,'style="display:inline;"') ?>	
			<div style="text-align:right;">
			<div id="t_tools" style="cursor:pointer;display:inline;" ><img  src="img/largeicons/tiny20x20/tools.jpg" />&nbsp;Tools</div>
			&nbsp;|&nbsp;<div id="t_legend" style="cursor:pointer;display:inline;" ><img src="img/largeicons/tiny20x20/flag.jpg" />&nbsp;Legend</div>
			
			</div>
			
			
			<div id="timelinetools" style="display:none;">	
				<b>Show:<b><br />
				<a href="start.php?timelinerange=0">1&nbsp;Day</a><br />	
				<a href="start.php?timelinerange=1">3&nbsp;Days</a><br />
				<a href="start.php?timelinerange=2">5&nbsp;Days</a><br />
				<a href="start.php?timelinerange=3">7&nbsp;Days</a><br />
				<b>Quick Jump:<b><br />
				<a href="start.php?timelineday=0">Today</a><br />	
				<a href="start.php?timelineday=<?=($day+10) ?>">10&nbsp;Days&nbsp;Forward</a><br />	
				<a href="start.php?timelineday=<?=($day-10) ?>">10&nbsp;Days&nbsp;Back</a><br />	
			<div>
			<div id="timelinelegend" style="display:none;width: 100px;">	
			<?
			$legendcount = 0;
			$legendoffset = 40;
			
			foreach($jobcolor as $name => $color) {
				echo '
					<div class="jobcontainer" style="position: relative;margin:5px;height: ' . $jobhight . 'px;width: 90%;">
						<div class="box">' . ucfirst($name) . '</div>
						<div class="jobbar" style="background-color: ' . $color . ';">&nbsp;</div>
					  </div>
				';
				$legendcount++;
			}
			?>
			</div>

<script>
	var jobids=new Array(<?= implode(",",$jobids) ?>);
	var jobstatus=new Array(<?= implode(",",$jobstatus) ?>);

	document.observe('dom:loaded', function() {
		$('canvas').blindDown({duration: 0.4});
		var cw = $('canvas').getWidth();
		for(i=0;i<<? echo $i ?>;i++){
			$('__' + i).tip = new Tip('__' + i, getcontent(i), {
				style: 'protogrey',
				radius: 4,
				border: 4,
				showOn: 'click',
				hideOn: false,
				hideAfter: 0.5,
				stem: 'topRight',
				hook: {  target: 'bottomMiddle', tip: 'topRight'  },
				width: '300px',
				viewport: true,
				offset: { x: 0, y: -5 }
			});
			var w = $('__' + i).getWidth();
			if(!w || !cw) {continue;}
			$('__' + i).style.width = "0%";
			$('__' + i).morph('width:' + ((w/cw)*100) + '%;',{duration: 1.5});
		}
		new Tip('t_tools', $('timelinetools').innerHTML, {
				style: 'protogrey',
				radius: 4,
				border: 4,
				hideOn: false,
				hideAfter: 0.5,
				stem: 'topRight',
				hook: {  target: 'bottomMiddle', tip: 'topRight'  },
				width: 'auto',
				offset: { x: 0, y: -4 }
			});
		new Tip('t_legend', $('timelinelegend').innerHTML, {
			style: 'protogrey',
			radius: 4,
			border: 4,
			hideOn: false,
			hideAfter: 0.5,
			stem: 'topRight',
			hook: {  target: 'bottomMiddle', tip: 'topRight'  },
			width: '150px',
			offset: { x: 0, y: -4 }
		});

		
	});
	function getcontent(id) {
		var content = $("box_" + id).innerHTML;
	//	content += '<br /><a href="job.php?id=' + jobids[id] + '">Edit</a><br />';
		if(jobstatus[id] == "Active" || jobstatus[id] == "Complete")
			content += '<div class="boxmonitor" onclick="popup(\'jobmonitor.php?jobid=' + jobids[id] + '\', 500, 450);" ><img src="graph_job.png.php?jobid=' + jobids[id] + '&junk=' + Math.random() + '"/><br />Click for larger view.</div>';		
		return content;
	}
	
	
	
</script>
