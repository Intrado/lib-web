<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Organization.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

$deleteorgids = array();
if (isset($_GET["delete"]) && isset($_GET["orgid"])) {
	// get the requested orgid for deletion
	$deleteorgid = $_GET["orgid"] + 0;
	
	// get the valid orgid
	$org = QuickQueryRow("select id, orgkey from organization where not deleted and id = ?", true, false, array($deleteorgid));
	
	if ($org) {
		
		// look up the org to see if any associations that prevent from being deleted
		$listentryorgid = QuickQuery("select o.id
											from organization o
											where o.id = ?
											and exists
												(select le.id
												from listentry le 
													inner join list l on 
														(le.listid = l.id)
													inner join user u on
														(l.userid = u.id)
												where not l.deleted and le.organizationid = o.id
													and not u.deleted)",
											false, array($org["id"]));
											
		$userassociationorgid = QuickQuery("select o.id
											from organization o
											where o.id = ?
											and exists
												(select ua.id
												from userassociation ua
													inner join user u on
														(ua.userid = u.id)
												where not u.deleted and ua.organizationid = o.id)",
											false, array($org["id"]));
											
		$persondatavaluesorgid = QuickQuery("select (pdv.value + 0) as orgid
											from persondatavalues pdv
											where pdv.fieldnum = 'oid' and pdv.value = ?",
											false, array($org["id"]));
											
		$personassociationorgid = QuickQuery("select o.id
											from organization o
											where o.id = ?
											and exists
												(select pa.id
												from personassociation pa
													inner join person p on
														(pa.personid = p.id)
												where not p.deleted and p.type = 'subscriber' and pa.organizationid = o.id)",
											false, array($org["id"]));
		
		if ($listentryorgid || $userassociationorgid || $persondatavaluesorgid || $personassociationorgid)
			$associatedorgid = true;
		else
			$associatedorgid = false;
		
		if ($associatedorgid) {
			notice('<div style="color: red; font-size: medium;">'. _L("The following Organization cannot be deleted because it has active associations"). '</div>'. 
				'<div style="text-align: left;"><ul><li>'. $org['orgkey']. '</li></ul></div>');
		} else {
			QuickUpdate("delete from personassociation where organizationid = ?", false, array($org["id"]));
			QuickUpdate("update organization set deleted = 1 where id = ?", false, array($org["id"]));
			notice(_L("Requested Organization: %s has been deleted", $org['orgkey']));
		}
	} else {
		notice(_L("Invalid organization id requested"));
	}
	redirect();
}

if (isset($_GET['deleteunassociated'])) {
	// get all org associations
	$listentryorgids = QuickQueryList("select le.organizationid, 1
											from listentry le
												inner join list l on
													(le.listid = l.id)
												inner join user u on
													(l.userid = u.id)
											where not l.deleted and le.type = 'organization'
												and not u.deleted
											group by le.organizationid",
											false, false, array());
	
	$userassociationorgids = QuickQueryList("select ua.organizationid, 1
											from userassociation ua
												inner join user u on
													(ua.userid = u.id)
											where not u.deleted and ua.type = 'organization'
											group by ua.organizationid",
											false, false, array());
	
	$persondatavaluesorgids = QuickQueryList("select (value + 0) as orgid, 1
											from persondatavalues
											where fieldnum = 'oid'
											group by orgid",
											false, false, array());
	
	$personassociationorgids = QuickQueryList("select pa.id, 1
											from personassociation pa
												inner join person p on
													(pa.personid = p.id)
											where not p.deleted and p.type = 'subscriber' and pa.type = 'organization'
											group by pa.organizationid",
											false, false, array());
	
	$associatedorgids = array();
	foreach ($listentryorgids as $orgid)
		$associatedorgids[$orgid] = true;
	foreach ($userassociationorgids as $orgid)
		$associatedorgids[$orgid] = true;
	foreach ($persondatavaluesorgids as $orgid)
		$associatedorgids[$orgid] = true;
	foreach ($personassociationorgids as $orgid)
		$associatedorgids[$orgid] = true;
	
	// if there are any associated org ids, query out the un-associated ones. Otherwise just get all the un-deleted ones.
	if ($associatedorgids)
		$unassociatedorgids = QuickQueryList("select id from organization where not deleted and id not in (". DBParamListString(count(array_keys($associatedorgids))). ")", false, false, array_keys($associatedorgids));
	else
		$unassociatedorgids = QuickQueryList("select id from organization where not deleted", false, false, array());
	
	// batch delete orgids 1k at a time
	if ($unassociatedorgids) {
			
		Query("BEGIN");
		$batch = array();
		foreach ($unassociatedorgids as $orgid) {
			$batch[] = $orgid;
			if (count($batch) == 1000) {
				QuickUpdate("delete from personassociation where importid is not null and organizationid in (". DBParamListString(count($batch)). ")", false, $batch);
				QuickUpdate("update organization set deleted = 1 where id in (". DBParamListString(count($batch)). ")", false, $batch);
				$batch = array();
			}
		}
		// get the last of the batch ids
		if ($batch) {
			QuickUpdate("delete from personassociation where importid is not null and organizationid in (". DBParamListString(count($batch)). ")", false, $batch);
			QuickUpdate("update organization set deleted = 1 where id in (". DBParamListString(count($batch)). ")", false, $batch);
			$batch = array();
		}
		Query("COMMIT");
		
		notice(_L("All un-associated Organizations have been deleted."));
	} else {
		notice (_L("There are no un-associated Organizations."));
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($row, $index) {
	global $start;
	return action_links(
		action_link("Rename", "pencil", "organizationrename.php?orgid=". $row[$index]),
		action_link("Merge", "fugue/arrow_join", "organizationmerge.php?orgid=". $row[$index]),
		action_link("Delete", "cross", "organizationdatamanager.php?orgid=". $row[$index] ."&delete&pagestart=$start","return confirm('". addslashes(_L('Are you sure you want to delete this organization?')) ."');"),
		action_link("Associations", "fugue/clear_folders__arrow", "organizationassociation.php?orgid=". $row[$index]));
}

$titles = array(
	"orgkey" => getSystemSetting("organizationfieldname","Organization"),
	"id" => 'Action');
$formatters = array(
	"id" => "fmt_actions");

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$data = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS id, orgkey from organization where not deleted order by orgkey, id limit $start, $limit", true);

$total = QuickQuery("select FOUND_ROWS()");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "Organization Manager";

include_once("nav.inc.php");

buttons(
	icon_button(_L("Done"), "fugue/tick", "document.location='settings.php';"),
	icon_button(_L("New"), "add", "document.location='organizationnew.php';"),
	icon_button(_L("Delete Un-associated"), "cross", "if(confirm('". addslashes(_L('Are you sure you want to delete all un-associated organizations?')) ."')) document.location='organizationdatamanager.php?&deleteunassociated'"));
	
startWindow(_L("Organizations"));

// if there are any organizations
if (count($data)) {
	showPageMenu($total, $start, $limit);
	?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($data, $titles, $formatters);
	?></table><?
	showPageMenu($total, $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No organizations defined"))?></div><?
}

endWindow();
buttons();

include_once("navbottom.inc.php");
?>