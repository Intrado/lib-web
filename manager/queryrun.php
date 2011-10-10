<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("dbmo/authserver/AspAdminQuery.obj.php");

if (!$MANAGERUSER->authorized("runqueries"))
	exit("Not Authorized");

$cid = false;
if (isset($_GET["cid"])) {
	$cid = $_GET["cid"] + 0;
}

if (isset($_GET['id'])) {
	$id = $_GET['id']+0;
	if ($MANAGERUSER->authQuery($id))
		$_SESSION['runquery'] = array("queryid" => $id, "cid" => $cid);
	redirect();
}


$managerquery = new AspAdminQuery($_SESSION['runquery']["queryid"]);
$cid = $_SESSION['runquery']["cid"];

$f = "editroles";
$s = "main";
$reloadform = 0;

$queryoutput = "";
if (CheckFormSubmit($f,$s)) {
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		
		//HACK work around workaround for sticky checkboxes
		//checkbox values get reset in NewFormItem, which happens to late for us to use the value
		PutFormData($f, $s, "savecsv",false,"bool", 0, 1);
		
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			
			
			$savecsv = GetFormData($f, $s, "savecsv");
			$enabledmode = GetFormData($f, $s, "customerenabledmode");
			
			if ($savecsv) {
				// Begin output to csv
				header("Pragma: private");
				header("Cache-Control: private");
				header("Content-disposition: attachment; filename=report.csv");
				header("Content-type: application/vnd.ms-excel");
			} else {
				$queryoutput .= '<table class=list width="100%">';
			}
			
			$limit = "";
			$args = array();
			// if only enabled customers requested
			if ($enabledmode == "enabled")
				$limit .= " and c.enabled";
			else if ($enabledmode == "disabled")
				$limit .= " and not c.enabled";
			//don't add anything if $enabledmode == "all"
			
			if ($cid) {
				$limit .= " and c.id = ?";
				$args[] = $cid;
			}
				
			//Following code mostly taken from query_customers, modified somewhat to fit this page	
			$query = "select c.id, s.dbhost, s.readonlyhost, s.dbusername, s.dbpassword, s.id from shard s inner join customer c on (c.shardid = s.id) where 1 $limit order by c.id";
			$res = Query($query, false, $args);
			
			$data = array();
			while($row = DBGetRow($res)){
				$data[] = $row;
			}
			
			class Foo123 { var $name; } //dummy class used to fill in something when no col data exists
			
			$wroteheaders = false;
			foreach($data as $customer){
	
				// if "usemaster", run the query on the master db, not the slave
				if ($managerquery->getOption("usemaster")) {
					$custdb = mysql_connect($customer[1], $customer[3], $customer[4]);
				} else {
					$custdb = mysql_connect($customer[2], $customer[3], $customer[4]);
				}
				mysql_select_db("c_$customer[0]", $custdb);
			
				$sqlquery = $managerquery->query;
				
				for ($x = 0; $x < $managerquery->numargs; $x++) {
					$sqlquery = str_replace('_$'.($x+1).'_', "'".DBSafe(GetFormData($f, $s, "arg_$x"))."'", $sqlquery);
				}
				
				
				$sqlquery = str_replace('_$CUSTOMERID_', $customer[0], $sqlquery);
				$res = mysql_query($sqlquery,$custdb)
					or die ("Failed to execute sql: " . mysql_error($custdb));
				
				$displayinfo = mysql_query("select value from setting where name = 'displayname'",$custdb)
					or die ("Failed to execute sql: " . mysql_error($custdb));
				$displayname = mysql_fetch_row($displayinfo);
				
				if ($savecsv) {
					//write field header
					if (!$wroteheaders) {
						$wroteheaders = true;
						echo '"customername","customerid"';
						for ($i = 0; $i < mysql_num_fields($res); $i++) {
							$field = mysql_fetch_field($res, $i);
							echo ',"' . $field->name . '"';
						}
						echo "\n";
					}
					
					while ($row = mysql_fetch_row($res)) {
						echo escape_csvfield($displayname[0]) . ',' . escape_csvfield($customer[0]) . ',' . array_to_csv($row) . "\n";
					}
				} else {
					$numfields = @mysql_num_fields($res);
					
					if (!$numfields) {
						$obj = new Foo123;
						$obj->name = "affected rows";
						$fields = array($obj);
						$sizes = array(13);
						$data = array(array(mysql_affected_rows()));
					} else {
						$fields = array();
						for ($i = 0; $i < $numfields; $i++) {
							$fields[] = mysql_fetch_field($res, $i);
						}
						
						$sizes = array();
						$data = array();
						while ($row = mysql_fetch_row($res)) {
							foreach ($row as $index => $col)
								$sizes[$index] = @max($sizes[$index],strlen($col),strlen($fields[$index]->name));
							$data[] = $row;
						}
					}
					
					if (count($data) > 0) { //don't show headers and stuff if there is no data
						$queryoutput .= '<tr class="listHeader"><th style="border-top: 1px solid black;" colspan=' . count($fields) . '>Customer: ' . escapehtml($displayname[0]) . ' (ID: ' . $customer[0] . ')</th></tr>';
						
						$line = '<tr class="listHeader">';
						foreach ($fields as $index => $field)
							$line .= '<th align="left">' . escapehtml($field->name) . '</th>';
						$queryoutput .= $line . "</tr>\n";	
						
						$counter = 0;
						foreach ($data as $row) {
							$line = '<tr '. ($counter++ % 2 == 1 ? 'class="listAlt"' : '') .'>';
							for ($i = 0; $i < count($row); $i++) {
								$line .= '<td>' . escapehtml($row[$i]) . '</td>';
							}
		
							$queryoutput .=  $line . "</tr>\n";
						}
					}
				}
			}//foreach customer
			
			if ($savecsv) {
				exit();
			} else {
				$queryoutput .= "</table><hr>";
			}
			
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ) {

	ClearFormData($f);
	
	for ($x = 0; $x < $managerquery->numargs; $x++) {
		PutFormData($f,$s,"arg_$x","","text","nomin","nomax",true);
	}
	
	PutFormData($f, $s, "savecsv",false,"bool", 0, 1);
	
	PutFormData($f, $s, "customerenabledmode","enabled","array", array("all","disabled","enabled"));
	
	
}
include_once("nav.inc.php");


NewForm($f);

if ($cid) {
	$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($cid));
?>
	<h2>Query results for customer: <?=$custurl?></h2>
<?
}?>

<table class=list width="100%">
	<tr>
		<th class="listHeader" align="left">Name</th>
		<td width=100%><?=escapehtml($managerquery->name)?></td>
	</tr>
	<tr class="listAlt">
		<th class="listHeader" align="left" valign=top>Notes</th>
		<td><?=nl2br(escapehtml($managerquery->notes))?></td>
	</tr>

<?
	$counter = 0;
	for ($x = 0; $x < $managerquery->numargs; $x++) {
?>
		<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
		<th class="listHeader" align="left">Arg <?=$x+1?></th>
		<td valign=top><? NewFormItem($f, $s, "arg_$x", 'text', "40"); ?></td>
		</tr>
<?
	}
	// dont show the "Enabled customers only" check box when querying a single customer
	if (!$cid) {
?>
	<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
		<th class="listHeader" align="left">Enabled&nbsp;Customers&nbsp;Search&nbsp;Mode</th>
		<td><? NewFormSelect($f, $s, "customerenabledmode", array("all" => "All","disabled" => "Disabled","enabled" => "Enabled"));?></td>
	</tr>
<?	}?>
	<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
		<th class="listHeader" align="left">Download&nbsp;CSV</th>
		<td><? NewFormItem($f, $s, "savecsv", "checkbox");?></td>
	</tr>
</table>

<? NewFormItem($f, $s, 'Run', 'submit')?>

<?
EndForm();


if ($queryoutput) {
?>
	<h1>Results:</h1>
	<?=$queryoutput?>
<?
}

include_once("navbottom.inc.php");
