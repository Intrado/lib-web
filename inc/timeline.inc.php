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
$jobcolor = array("scheduled" => "#ffff00","processing"=>"#ff5500","active" => "#00ff00","complete" => "#3399ff","cancelling" => "#ff0000","cancelled" => "#00ffff");
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
				}
			}

			$jobids[$i] = "'$job->id'";
			$jobstatus[$i] = "'" . ucfirst($job->status) . "'";
			$statuscolor = (isset($jobcolor[$job->status])?$jobcolor[$job->status]:"#000");

			$content .= '<div id="__' . $i. '" class="jobcontainer" style="left: ' . $startlocation . '%;top: ' . (($j*$jobhight) + ($j*$jobspacing)) . 'px;height: ' . $jobhight . 'px;width: ' . $width . '%;">';
			$content .= '<div class="jobname">';
			$content .= escapehtml($job->name);
			$content .= '</div>
						<div id="box_' . $i . '" style="display:none"><div class="box">
							<div class="boxname">' . escapehtml($job->name) . '</div>
							<div class="jobbar" style="position: relative;background-color: ' . $statuscolor . ';top:3px;height:auto;">Status:&nbsp;' .  ucfirst($job->status) . '</div>
							<p id="boxc_' . $i . '" >';
			//$content .= 'Start Time: ' .$job->starttime. '<br />End Time: ' . $job->endtime. '<hr />';
			$content .= fmt_timeline_jobs_actions($job,"job") . '</p>
							</div>
						</div>
						<div class="jobbar" style="background-color: ' . $statuscolor . ';">&nbsp;</div>
						</div>';

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
	
<div class="content_row">
	
		<table class="timeline_table">
			<tr>
				<td class="timeline_table_left">
				<a id="_backward" href="start.php?timelineday=<?= ($day-($range>0?$range*2:1)) ?>"></a>
				</td>

				<td class="timeline_table_middle" >

					<div id="canvas" style="height:<? echo $minhight ?>px;">
					<img class="canvasleft" src="img/timelineleft.gif"  alt="" />
					<img class="canvasright" src="img/timelineright.gif" alt="" />
					<div class="daylineend" style="left: 0%;"></div>
		
					<?
						for($k=1;$k < $rangedays;$k++){
							echo '<div class="dayline" style="left: ' . ($rangeslice * $k) . '%;"></div>';
							echo '<div class="daylinetop" style="left: ' . ($rangeslice * $k) . '%;"></div>';
							echo '<div class="daylinebottom" style="left: ' . ($rangeslice * $k) . '%;"></div>';
						}
					 ?>
					<div class="daylineend" style="left: 100%;"></div>

					<?
					$labelstart = ($centerposition - $rangestart) + 43200;
					$today = date("M jS");
					for($k=0;$k < $rangedays;$k++){
						$daylabel = date("M jS", $labelstart + 86400 * $k);
						if($daylabel==$today) {
							$daylabel = str_replace(" ","&nbsp;",$daylabel);
							echo '<div class="daylabeltoday" style="left: ' . ($rangeslice * $k) . '%;width: ' . $rangeslice . '%;">Today<br />(' . $daylabel . ')</div>';
						} else
							echo '<div class="daylabel" style="left: ' . ($rangeslice * $k) . '%;width: ' . $rangeslice . '%;">' . $daylabel . '</div>';
					}
					echo $content;
					?>
					</div><!-- canvas -->
					
			</td>
			<td class="timeline_table_right">
				<a id="_forward" href="start.php?timelineday=<?= ($day + ($range>0?$range*2:1)) ?>"></a>
			</td>
		</tr>
		</table>
</div><!-- content_row -->
	
<div class="content_row cf" >

		<div id="t_refresh">
			<?=	icon_button(_L('Refresh'),"fugue/arrow_circle_double_135","window.location.reload()",null,'style="display:inline;"') ?>
		</div>
		
		<div id="t_zoom">
			<?
			if($range > 0 ) {
				echo '<a class="zoom_in" href="start.php?timelinerange=' .  ($range-1) . '">Zoom in</a> |';
			}
			?>
			<a class="reset" href="start.php?timelinerange=1&timelineday=0">Reset</a>
			<?
			if($range < 5 ) {
				echo '| <a class="zoom_out" href="start.php?timelinerange=' .  ($range+1) . '">Zoom out</a>';
			}
			?>
		</div>

		<div id="t_legend">
			<span>Legend</span>
		</div>

</div><!-- .content_row -->
			
			<div id="timelinelegend" style="display:none;width: 100px;">
				<?
				foreach($jobcolor as $name => $color) {
					echo '<div class="jobcontainer" style="cursor:default;position:relative;margin:5px;height: ' . $jobhight . 'px;">
									<div class="box" style="padding-top:3px;">&nbsp;' . ucfirst($name) . '&nbsp;</div>
									<div class="jobbar" style="background-color: ' . $color . ';">&nbsp;</div>
						  	</div>';
				}
			?>
			</div><!-- timelinelegend -->
			
<script>
	var jobids=new Array(<?= implode(",",$jobids) ?>);
	var jobstatus=new Array(<?= implode(",",$jobstatus) ?>);

	document.observe('dom:loaded', function() {
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
			$('__' + i).jobid = jobids[i];
			$('__' + i).jobstatus = jobstatus[i];
			$('__' + i).observe('prototip:shown', function() {
				if((this.jobstatus=="Active" || this.jobstatus=="Cancelling" ) && $('boxmonitor_' + this.jobid) != undefined) {
					$('boxmonitor_' + this.jobid).update('<img src="graph_job.png.php?jobid=' + this.jobid + '&junk=' + Math.random() + '"/><br />Click for more information.');
				}
			});
			var w = $('__' + i).getWidth();
			if(!w || !cw) {continue;}
			$('__' + i).style.width = "0%";
			$('__' + i).morph('width:' + ((w/cw)*100) + '%;',{duration: 1.5});
		}
		new Tip('t_legend', $('timelinelegend').innerHTML, {
			style: 'protogrey',
			radius: 4,
			border: 4,
			hideOn: false,
			hideAfter: 0.5,
			stem: 'bottomMiddle',
			hook: {  target: 'rightMiddle', tip: 'rightBottom'  },
			width: '120px',
			offset: { x: 0, y: -4 }
		});


	});
	function getcontent(id) {
		var content = $("box_" + id).innerHTML;
		if(jobstatus[id] == "Active" || jobstatus[id] == "Complete" || jobstatus[id] == "Cancelling" || jobstatus[id] == "Cancelled" ){
			var jobid = jobids[id];
			content += '<div id="boxmonitor_' + jobid + '" class="boxmonitor" onclick="popup(\'jobmonitor.php?jobid=' + jobid + '\', 650, 450);" ><img src="graph_job.png.php?jobid=' + jobid + '&junk=' + Math.random() + '"/><br />Click for more information.</div>';
		}
		return content;
	}


</script>
