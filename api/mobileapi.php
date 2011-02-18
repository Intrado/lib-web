<? 

//do version check before any auth/session/etc
if ($_GET['requesttype'] == "checkversion") {
	header('Content-Type: application/json');
	if ($_GET['version'] == "1.0") //or other suported version
		echo json_encode(array("resultcode" => "success", "resultdescription" => 
			"This Version is supported"));
	else if ($_GET['version'] == "0.1")
		echo json_encode(array("resultcode" => "warn", "resultdescription" => 
			"A new version of this application is available. You can continue, but this "
			."version may not be supported in the future."));
	else 
		echo json_encode(array("resultcode" => "failure", "resultdescription" => 
			"This Version is not supported. Please update to continue using this application."));
	
	exit();
}

require_once("../inc/subdircommon.inc.php");
require_once("../obj/Job.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/RenderedList.obj.php");


/**
 * Similar to showTable
 * @param unknown_type $data array of objects
 * @param unknown_type $titles ordered name->title map (unlisted fields are not returned)
 * @param unknown_type $formatters name->formatter name formatter callbacks.
 * @return {titles : $titles, items: [...]}
 */
function formatObjects ($data, $titles, $formatters = array()) {
	$outputTitles = array_values($titles);
	
	$items = array();
	foreach ($data as $obj) {
		//only show cels with titles
		$item = array();
		foreach ($titles as $name => $title) {
			if (isset($formatters[$name])) {
				$fn = $formatters[$name];
				$cel = $fn($obj,$name);
			} else {
				$cel = $obj->$name;
			}
			$item[] = $cel;
		}
		$items[] = $item;
	}
	
	return array("titles" => $outputTitles,"items" => $items);
}

function doJobCancel($cancelid) {
	$didCancel = false;
	if (userOwns("job",$cancelid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($cancelid);
		$didCancel = $job->cancel();
	}	
	if ($didCancel)
		return array("resultcode" => "success", "resultdescription" => "");
	else
		return array("resultcode" => "failure", "resultdescription" => _L("There was a problem trying to cancel this job."));
}

function doJobArchive($archiveid) {
	$didArchive = false;
	if (userOwns("job",$archiveid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($archiveid);
		$didArchive = $job->archive();
	}	
	if ($didArchive)
		return array("resultcode" => "success", "resultdescription" => "");
	else
		return array("resultcode" => "failure", "resultdescription" => _L("There was a problem trying to archive this job."));
}


function doJobView($start,$limit) {
	global $USER;
	if ($start < 0 ) 
		$start = 0;
	
	$total = 0;
	$query = "from job where userid=$USER->id 
			and status not in ('new','repeating','survey') 
			and deleted=0 
			order by ifnull(finishdate,modifydate) desc";
	$data = DBFindMany("Job",$query . " limit $start, $limit");
	$total = QuickQuery("select count(id) " . $query) + 0;

	$displaystart = ($total) ? $start +1 : 0;
	
	$titles = array("id" => "id",
				"name" => "name",
				"description" => "description",
				"jobtypeid" => "jobtypeid",
				"status" => "status",
				"startdate" => "startdate",
				"enddate" => "enddate",
				"starttime" => "starttime",
				"endtime" => "endtime"
				);
	$formatters = array('Status' => 'fmt_status',
					"type" => "fmt_obj_delivery_type_list");
	return array_merge(array("resultcode" => "success", "resultdescription" => "",
							"totalrows" => $total,
							"startrow" => $displaystart
						),
						formatObjects($data,$titles,$formatters));
}

function doJobInfo($jobid) {
	if (!userOwns("job",$jobid))
		return array("resultcode" => "failure", "resultdescription" => _L("You do not have to view information for this job."));
		
	$listids = QuickQueryList("select listid from joblist where jobid = ?",false,false,array($jobid));
	$lists = array();
	
	$totallistcount = 0;
	foreach ($listids as $id) {
		if (!userOwns('list', $id) && !isSubscribed("list", $id))
			continue;
		
		$list = new PeopleList($id+0);
		$renderedlist = new RenderedList2();
		$renderedlist->pagelimit = 0;
		$renderedlist->initWithList($list);
		
		$count = $renderedlist->getTotal() + 0;
		$totallistcount+= $count;
		$lists[] = array("id" => $list->id, "name" => $list->name,"contacts" => $count);
	}
	
	$job = new Job($jobid);
	$jobtype = new JobType($job->jobtypeid);
	$jobinfo = array(	"id" => $job->id,
			"name" => $job->name,
			"description" => $job->description,
			"jobtype" => array("id" => $jobtype->id, "name" => $jobtype->name, "info" => $jobtype->info),
			"lists" => $lists,
			"messagegroup" => $job->messagegroupid,
			"messagetypes" => array("phone" => $job->hasPhone(),"email" => $job->hasEmail(),"sms" => $job->hasSMS()),
			"status" => $job->status,
			"startdate" => $job->startdate,
			"enddate" => $job->enddate,
			"starttime" => $job->starttime,
			"endtime" => $job->endtime
			//,"options" => $job->optionsarray
			);

	return array("resultcode" => "success", "resultdescription" => "","jobinfo" => $jobinfo);	
}

function doliststats($statsids) {
	$listids = json_decode($statsids);
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

function handleRequest() {
	global $USER, $ACCESS;
	
	if (!isset($_GET['version']) || !isset($_GET['requesttype'])) {
		return array("resultcode" => "failure", "resultdescription" => "Invalid request. Please upgrade the application.");
	}
	
	if ($_GET['version'] == "1.0") {
		
		//stuff related to this version's API
		if ($_GET['requesttype'] == "job") {
			if (isset($_GET['cancel'])) {
				return doJobCancel($_GET['cancel']);
			} else if (isset($_GET['archive'])) {
				return doJobArchive($_GET['archive']);
			} else if (isset($_GET['view'])) {
				$limit = 0 + (isset($_GET['limit']) ? $_GET['limit'] : 20);
				$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
				return doJobView($start,$limit);
			} else if (isset($_GET['info'])) {
				return doJobInfo($_GET['info']);
			}
		} else if ($_GET['requesttype'] == "list") {
			if (isset($_GET['stats'])) {
				return doliststats($_GET['stats']);
			}
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