<?
// phone inbound, prompt to select jobtype (page into sets of 9), then save jobtypeid

include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../inc/utils.inc.php");
include_once("inboundutils.inc.php");
include_once("../obj/JobType.obj.php");

global $BFXML_VARS;

$PAGESIZE = 9;


function loadJobtypesDB()
{
	loadUser(); // load the user to obtain the permitted job types
	global $USER;
	return JobType::getUserJobTypes();
}

function loadJobtypes($incr)
{
	global $PAGESIZE, $USER;
	if (!isset($PAGESIZE)) $PAGESIZE = 9; // this is strange... why isnt it set the first time from above???
	//error_log("pagesize: ".$PAGESIZE);
	////error_log("loadjobtypes current page ".$_SESSION['currentJobtypePage']);

	$allJobtypes = array_values(loadJobtypesDB()); // convert indexes to 0, 1, 2, ...

	// if first time, set to 0
	if (!isset($_SESSION['currentJobtypePage'])) {
		$_SESSION['currentJobtypePage'] = 0;
	// if increment
	} else if ($incr) {
		$_SESSION['currentJobtypePage']++;
		// if page wrap to beginning
		if (count($allJobtypes) <= ($_SESSION['currentJobtypePage'])*$PAGESIZE) {
			$_SESSION['currentJobtypePage'] = 0;
		}
	}

	//error_log("currentJobtypePage: ".$_SESSION['currentJobtypePage']);

	$_SESSION['hasPaging'] = false;
	if (count($allJobtypes) > $PAGESIZE) {
		$_SESSION['hasPaging'] = true;
	}
	// group jobtypes into sets of 9 (digits 1-9 on the phone)
	$jobtypeSubset = array_slice($allJobtypes, $_SESSION['currentJobtypePage']*$PAGESIZE, $PAGESIZE, true);
	return $jobtypeSubset; // the list of jobtypes for this user, page includes no more than 9
}

function playJobtypes($incr, $playprompt=true)
{
	$jobtypes = loadJobtypes($incr);
?>
<voice >
	<message name="jobtypedirectory">
<?	if (count($jobtypes) == 0) { ?>
		<audio cmid="file://prompts/inbound/NoJobTypes.wav" />
		<hangup />
<?	} ?>

		<field name="jobtypenumber" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">

<?				if ($playprompt) { ?>
					<audio cmid="file://prompts/inbound/SpecifyJobType.wav" />
<?				} ?>

<?
				$jobtypeindex = 1;
				foreach ($jobtypes as $jobtype)
				{
?>
					<audio cmid="file://prompts/inbound/Press<?= $jobtypeindex ?>For.wav" />
					<tts gender="female"><?= htmlentities($jobtype->name, ENT_COMPAT, "UTF-8") ?></tts>
<?
					$jobtypeindex++;
				}
				// if jobtypes are on pages, provide * option
				if ($_SESSION['hasPaging']) {
?>
					<audio cmid="file://prompts/inbound/MoreJobs.wav" />
<?
				}
?>
			</prompt>

<?
			$jobtypeindex = 1;
			foreach ($jobtypes as $jobtype)
			{
?>
				<choice digits="<?= $jobtypeindex ?>" />
<?
				$jobtypeindex++;
			}
			// if jobtypes are on pages, provide * option
			if ($_SESSION['hasPaging']) {
?>
				<choice digits="*" />
<?
			}
?>

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}


/////////////////

if($REQUEST_TYPE == "new"){
	?>
	<error>inboundjobtype: wanted continue, got new </error>
	<?
} else if($REQUEST_TYPE == "continue") {

	// if they selected a jobtype
	if (isset($BFXML_VARS['jobtypenumber'])) {

		$jobtypenumber = $BFXML_VARS['jobtypenumber'];
		//error_log("jobtype number selected: ".$jobtypenumber);

		// if they want to hear the next page of jobtypes
		if ($jobtypenumber == "*") {
			playJobtypes(true, false);
		// else save jobtype selection and move to job options
		} else {

			$jobtypeindex = ($_SESSION['currentJobtypePage']*$PAGESIZE)+($jobtypenumber-1);
			//error_log("jobtypeindex: ".$jobtypeindex);

			$jobtypes = array_values(loadJobtypesDB()); // convert indexes to 0, 1, 2, ...
			//var_dump($jobtypes);
			$jobtype = $jobtypes[$jobtypeindex];
			//error_log("jobtype name: ".$jobtype->name);

			$_SESSION['priority'] = $jobtype->id;

			forwardToPage("inboundjob.php");

		}
	// play the current page of jobtypes
	} else {
		playJobtypes(true);
	}

} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>
