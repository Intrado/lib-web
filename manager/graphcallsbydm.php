<?

require_once("common.inc.php");
include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_bar.php");

if(! $MANAGERUSER->authorized("aspcallgraphs"))
	exit("Not Authorized");

if (! isset($SETTINGS['aspcalls'])) {
	exit('aspcalls is not configured');
}

session_write_close();

$resultValues = array('answered','machine','busy', 'noanswer','badnumber','fail','trunkbusy','unknown','hangup');

function fetchData($nrql) {
	$data = array();
	$ch = curl_init("https://insights-api.newrelic.com/v1/accounts/379119/query?nrql=" . urlencode($nrql));

	// returned data will be a string
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json", "X-Query-Key: nmPXPhnclYyjHNuKRyKKCihjX_e3Kc--"));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// run it, get result.
	$result = curl_exec($ch);

	// if cURL could not execute
	if (! $result) {
		$errorString = "cURL request  <b style='color:darkred'>FAILED</b> attempting to update shortcode groups for: (<b>$domain)</b>) --stack trace: " . curl_error($ch);

		error_log($errorString);

		return $data;
	}

	$resultArray = json_decode($result, true);
	foreach ($resultArray['facets'] as $facet) {
		$data[$facet['name']] = $facet['results'][0]['count'];
	}

	return $data;
}

$query = "select  dm.name
			from dm dm
			left join dmsetting s_dm_enabled on
					(dm.id = s_dm_enabled.dmid
					and s_dm_enabled.name = 'dm_enabled')
			where dm.type = 'system'
			 and s_dm_enabled.value = '1' and dm.enablestate='active'";

$result = Query($query);

$dmNames = array();
while($row = DBGetRow($result)) {
	$dmNames[]=$row[0];
}

$resultData = array();
foreach ($resultValues as $resultValue) {
	$nrql = '';
	if ($resultValue == 'hangup') {
		$nrql = "SELECT count(*) from notification where appName='prod/sysDispatcher' and name='phone' and result in ('answered','machine') and earlyHangup is true since 24 hours ago LIMIT 200 FACET dmName";
	} else {
		$nrql = "SELECT count(*) from notification where appName='prod/sysDispatcher' and name='phone' and result='$resultValue' since 24 hours ago facet dmName LIMIT 200";
	}
	$fetchedData = fetchData($nrql);
	foreach ($dmNames as $dmName) {
		if (isset($fetchedData[$dmName])) {
			if ($resultValue != "answered" && $resultValue != "machine" && $resultValue != "hangup") {
				$resultData[$resultValue][$dmName] = -$fetchedData[$dmName];
			} else {
				$resultData[$resultValue][$dmName] = $fetchedData[$dmName];
			}
		} else {
			$resultData[$resultValue][$dmName] = 0;
		}
	}
}

$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Y-m-d", time() - 60*60*24*30); //default 30 days
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Y-m-d");

$x = 0;
$data = $resultData;
$titles = $dmNames;

$cpcolors = array(
	"answered" => "lightgreen",
	"machine" => "#1DC10",
	"hangup" => "purple",
	"busy" => "orange",
	"noanswer" => "tan",
	"badnumber" => "black",
	"fail" => "red",
	"trunkbusy" => "yellow",
	"unknown" => "dodgerblue"
);
/*
echo "Dms " . count($dmNames);
echo "Answered " . count($data['answered']);
echo "Machine " . count($data['machine']);
echo "HangUp " . count($data['hangup']);
echo "Busy " . count($data['busy']);
echo "noAnswer " . count($data['noanswer']);
echo "badnumber " . count($data['badnumber']);
echo "fail " . count($data['fail']);
echo "trunkbusy " . count($data['trunkbusy']);
echo "unknown " . count($data['unknown']);
*/
// Create the graph. These two calls are always required
$graph = new Graph(1400, 600,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(60,90,20,70);

$b1plot = new BarPlot(array_values($data["answered"]));
$b1plot->SetFillColor($cpcolors["answered"]);
$b1plot->SetLegend("Answered");

$b2plot = new BarPlot(array_values($data["machine"]));
$b2plot->SetFillColor($cpcolors["machine"]);
$b2plot->SetLegend("Machine");

$b9plot = new BarPlot(array_values($data["hangup"]));
$b9plot->SetFillColor($cpcolors["hangup"]);
$b9plot->SetLegend("Hangup");

$b3plot = new BarPlot(array_values($data["badnumber"]));
$b3plot->SetFillColor($cpcolors["badnumber"]);
$b3plot->SetLegend("Disconnect");

$b4plot = new BarPlot(array_values($data["fail"]));
$b4plot->SetFillColor($cpcolors["fail"]);
$b4plot->SetLegend("Fail");

$b5plot = new BarPlot(array_values($data["busy"]));
$b5plot->SetFillColor($cpcolors["busy"]);
$b5plot->SetLegend("Busy");

$b6plot = new BarPlot(array_values($data["noanswer"]));
$b6plot->SetFillColor($cpcolors["noanswer"]);
$b6plot->SetLegend("No Answer");

$b7plot = new BarPlot(array_values($data["trunkbusy"]));
$b7plot->SetFillColor($cpcolors["trunkbusy"]);
$b7plot->SetLegend("Trunkbusy");

$b8plot = new BarPlot(array_values($data["unknown"]));
$b8plot->SetFillColor($cpcolors["unknown"]);
$b8plot->SetLegend("Unknown");

// Create the grouped bar plot
$gbplot = new AccBarPlot(array($b6plot,$b5plot,$b4plot,$b3plot,$b8plot,$b7plot,$b9plot,$b2plot,$b1plot));
$gbplot->SetWidth(0.7);


// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("By DM $startdate to $enddate");
$graph->xaxis->SetTickLabels($titles);
$graph->xaxis->SetLabelAngle(90);

$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
$graph->yaxis->SetTextLabelInterval(3);
$graph->legend->Pos(0.00,0.25,"right","center");


$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();


?>
