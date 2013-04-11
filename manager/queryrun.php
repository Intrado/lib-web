<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../inc/themes.inc.php");
require_once("dbmo/authserver/AspAdminQuery.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

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


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();


if ($cid) {
	$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($cid));
	$formdata[] = _L("Query results for customer: %s", $custurl);
}

$formdata["name"] = array(
		"label" => _L("Name"),
		"control" => array("FormHtml","html"=>"<div>".escapehtml($managerquery->name)."</div>"),
		"helpstep" => 1
);
$formdata["notes"] = array(
		"label" => _L("Notes"),
		"control" => array("FormHtml","html"=>"<div>".nl2br(escapehtml($managerquery->notes))."</div>"),
		"helpstep" => 1
);
$counter = 0;
for ($x = 0; $x < $managerquery->numargs; $x++) {
	$formdata["arg" . $x] = array(
			"label" => _L("Arg " . ($x+1)),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("TextField","size"=>40),
			"helpstep" => 1
	);
}

if (!$cid) {
	$formdata["customerenabledmode"] = array(
			"label" => _L('Enabled Customers Search Mode'),
			"value" => "All",
			"validators" => array(),
			"control" => array("SelectMenu", "values" => array("all" => "All","disabled" => "Disabled","enabled" => "Enabled")),
			"helpstep" => 1
	);
}
$formdata["savecsv"] = array(
		"label" => _L('Download CSV'),
		"value" => "",
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 1
);


$buttons = array(icon_button(_L('Back'),"fugue/arrow_180",null,"querylist.php" . (isset($cid)?"?cid=$cid":"")),
				submit_button(_L('Run'),"submit","tick"));
$form = new Form("queryform",$formdata,null,$buttons);
$form->ajaxsubmit = false;
$queryoutput = "";
////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		session_write_close(); //don't lock session
		set_time_limit(900); //15 minutes
		
		$savecsv = $postdata["savecsv"];
		$enabledmode = isset($postdata["customerenabledmode"])?$postdata["customerenabledmode"]:false;
			
		if ($savecsv) {
			// Begin output to csv
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.csv");
			header("Content-type: application/vnd.ms-excel; charset=UTF-8");
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
		
		class Foo123 { var $name;
		} //dummy class used to fill in something when no col data exists
			
		$wroteheaders = false;
		foreach($data as $customer){
		
			// if "usemaster", run the query on the master db, not the slave
			if ($managerquery->getOption("usemaster")) {
				$custdb = mysql_connect($customer[1], $customer[3], $customer[4]);
			} else {
				$custdb = mysql_connect($customer[2], $customer[3], $customer[4]);
			}
			mysql_select_db("c_$customer[0]", $custdb);
		
			//set charset for utf8
			$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
			mysql_query($setcharset, $custdb);
				
			$sqlquery = $managerquery->query;
		
			for ($x = 0; $x < $managerquery->numargs; $x++) {
				$sqlquery = str_replace('_$'.($x+1).'_', "'".DBSafe($postdata["arg$x"])."'", $sqlquery);
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

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Queries');
$PAGE = "advanced:queries";

include_once("nav.inc.php");

startWindow(_L('Run Query'));

//
$posturl = $_SERVER['REQUEST_URI'];
$pos = mb_strpos($posturl,"?");
if ($pos !== false) {
	$_SERVER['REQUEST_URI'] = substr($posturl,0,$pos);
}

echo $form->render();
endWindow();

if ($queryoutput) {
	startWindow(_L('Results'));
	echo $queryoutput;
	endWindow();

}
include_once("navbottom.inc.php");
?>
