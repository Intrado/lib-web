<? 

require_once("../inc/subdircommon.inc.php");
require_once("../obj/Job.obj.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/RenderedList.obj.php");


function jsonFormatObjects ($data, $titles, $formatters = array()) {
	$outputTitles = array();
	foreach ($titles as $title) {
		if (strpos($title,"#") === 0)
			$title = substr($title,1);
		$outputTitles[] = $title;
		//$outputTitles[] = escapehtml($title);
	}

	$items = array();
	foreach ($data as $obj) {
		//only show cels with titles
		
		$item = array();
		foreach ($titles as $index => $title) {
			if (isset($formatters[$index])) {
				$fn = $formatters[$index];
				$cel = $fn($obj,$index);
			} else {
				$cel = $obj->$index;
				//$cel = escapehtml($obj->$index);
			}
			if (strpos($title,"#") === 0)
				$title = substr($title,1);
			$item[$title] = $cel;
		}
		$items[] = $item;
	}
	
	return array("titles" => $outputTitles,"items" => $items);
}

function jobdatecompare($a, $b) {
	$acompleted = $a->status == "completed" || $a->status == "cancelled";
	$bcompleted = $b->status == "completed" || $b->status == "cancelled";
	
	if($acompleted && $bcompleted) {
		return $a->finishdate == $b->finishdate ? 0 : ($a->finishdate > $b->finishdate ? -1 : 1);	
	} else if ($acompleted){
		return $a->finishdate == $b->modifydate ? 0 : ($a->finishdate > $b->modifydate ? -1 : 1);
	} else if ($bcompleted){
		return $a->modifydate > $b->finishdate ? 0 : ($a->modifydate > $b->finishdate ? -1 : 1);
	}
	
	return $a->modifydate == $b->modifydate ? 0 : ($a->modifydate > $b->modifydate ? -1 : 1);
}



function handleRequest() {
	global $USER, $ACCESS;
	
	if (!isset($_GET['version']) || !isset($_GET['requesttype'])) {
		return array("resultcode" => "failure", "resultdescription" => "Invalid request. Please upgrade the application.");
	}
	
	if ($_GET['version'] == "1.0") {
		if ($_GET['requesttype'] == "job") {
			if (isset($_GET['cancel'])) {
				$cancelid = DBSafe($_GET['cancel']);
				if (userOwns("job",$cancelid) || $USER->authorize('managesystemjobs')) {
					$job = new Job($cancelid);
					$job->cancelleduserid = $USER->id;
			
					Query("BEGIN");
						if ($job->status == "active" || $job->status == "procactive" || $job->status == "processing" || $job->status == "scheduled") {
							$job->status = "cancelling";
						} else if ($job->status == "new") {
							$job->status = "cancelled";
							$job->finishdate = QuickQuery("select now()");
							//skip running autoreports for this job since there is nothing to report on
							QuickUpdate("update job set ranautoreport=1 where id='$cancelid'");
						}
						$job->update();
					Query("COMMIT");
					return array("resultcode" => "success", "resultdescription" => "");
				} 
				return array("resultcode" => "failure", "resultdescription" => _L("You do not have permission to cancel this job."));
			}
			
			if (isset($_GET['archive'])) {
				$archiveid = DBSafe($_GET['archive']);
				if (userOwns("job",$archiveid) || $USER->authorize('managesystemjobs')) {
					$job = new Job($archiveid);
					if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
						Query('BEGIN');
							$job->deleted = 2;
							$job->modifydate = date("Y-m-d H:i:s", time());
							$job->update();
						Query('COMMIT');
						return array("resultcode" => "success", "resultdescription" => "");
					} else {
						return array("resultcode" => "failure", "resultdescription" => _L("The job, is still running. Please cancel it first."));
					}
				}
				return array("resultcode" => "failure", "resultdescription" => _L("You do not have permission to archive this job."));
			}
			
			if (isset($_GET['joblist'])) {
					$limit = 100;
					$start = 0 + (isset($_GET['page']) ? $_GET['page'] : 0);
					
					if ($_GET['joblist'] === "active") {
						$data = DBFindMany("Job","from job where userid=$USER->id and (status='new' or status='scheduled' or status='procactive' or status='processing' or status='active' or status='cancelling') and type != 'survey' and deleted=0 order by modifydate desc limit $start, $limit");
					} else if($_GET['joblist'] === "completed") {
						$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0 order by finishdate desc limit $start, $limit");
					} else  {
						$data = DBFindMany("Job","from job where userid=$USER->id and status!='repeating' and type != 'survey' and deleted=0  order by modifydate desc limit $start, $limit");
						uasort($data, 'jobdatecompare');
					}
					
					$total = QuickQuery("select FOUND_ROWS()");
					$numpages = ceil($total/$limit);
					$curpage = ceil($start/$limit) + 1;
					$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
					$displaystart = ($total) ? $start +1 : 0;
					//TODO return paging information
					
					$titles = array("id" => "id",
								"name" => "Job Name",
								"description" => "#Description",
								"startdate" => "Start date",
								"status" => "Status");
					$formatters = array('Status' => 'fmt_status',
									"type" => "fmt_obj_delivery_type_list",
									"startdate" => "fmt_job_startdate");
					return array_merge(array("resultcode" => "success", "resultdescription" => ""),
										jsonFormatObjects($data,$titles,$formatters));
			}
		} else if ($_GET['requesttype'] == "list") {
			if (isset($_GET['statsforids'])) {
				$listids = json_decode($_GET['statsforids']);
				if (!is_array($listids))
					return false;
				$stats = array();
				foreach ($listids as $id) {
					if (!userOwns('list', $id) && !isSubscribed("list", $id))
						continue;
					$list = new PeopleList($id+0);
					$renderedlist = new RenderedList2();
					$renderedlist->pagelimit = 0;
					$renderedlist->initWithList($list);
					$stats[$list->id]= array(
						'name' => $list->name,
						'advancedlist' => false, //TODO remove this
						'totalremoved' => $list->countRemoved(),
						'totaladded' => $list->countAdded(),
						'totalrule' => -999, //TOOD remove this
						'total' => $renderedlist->getTotal() + 0);
				}
				return array("resultcode" => "success", "resultdescription" => "","stats" => $stats);
			}
		}
		else if ($_GET['requesttype'] == "checkversion") {
			return array("resultcode" => "success", "resultdescription" => "This Version is supported");
			// If failure is returned here the user will be prompted with the resultdescription after login in the iPhone application
			//return array("resultcode" => "failure", "resultdescription" => "Please upgrade the application. Some functionality in this application may no longer be supported.");
		}
	} else  {
		return array("resultcode" => "failure", "resultdescription" => "Please upgrade the application. This version is no longer supported.");
	}
	// Unkown API request
	return array("resultcode" => "failure", "resultdescription" => "Please upgrade the application. Some functionality in this application may no longer be supported.");
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);


?>