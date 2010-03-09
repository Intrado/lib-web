<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");
include ("jpgraph/jpgraph_canvas.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point


$big = isset($_GET['big']) ? true : false;


function limit_str ($txt, $max = 25) {
	if (strlen($txt) > $max)
		return substr($txt,0,$max-3) . "...";
	else
		return $txt;
}


$query = "
select count(*) as cnt, j.name, u.login
 from reportperson rp, job j, user u
where rp.jobid = j.id and u.id = j.userid
and rp.status='success'
and j.finishdate >= date_sub(now(), interval 30 day)
group by j.id
order by cnt desc
limit 15
";

$result = Query($query);

$y_titles = array();
$data = array();
while ($row = DBGetRow($result)) {
	$data[] = $row[0];
	$y_titles[] = limit_str($row[1],20) . " (" . $row[2] . ")";
}


if (count($data) == 0) {
	$graph = new CanvasGraph(400,300,"auto");
	$t1 = new Text("Sorry, there is no data to display");
	$t1->SetPos(0.05,0.5);
	$t1->SetOrientation("h");
	$t1->SetFont(FF_FONT1,FS_NORMAL);
	$t1->SetBox("white","black",'gray');
	$t1->SetColor("black");
	$graph->AddText($t1);
	$graph->Stroke();
	exit();
}

//var_dump($data);
//exit();

// Set the basic parameters of the graph
$graph = new Graph($big ? 750 : 350, $big ? 450 : 300,'auto');
$graph->SetScale("textlin");
$graph->SetFrame(false);

// Rotate graph 90 degrees and set margin
$graph->Set90AndMargin(200,20,30,30);

// Nice shadow
//$graph->SetShadow();

// Setup title
$graph->title->Set("By Jobs (last 30 days)");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Setup X-axis
$graph->xaxis->SetTickLabels($y_titles);
$graph->xaxis->SetFont(FF_FONT1,FS_NORMAL);

// Some extra margin looks nicer
$graph->xaxis->SetLabelMargin(10);

// Label align for X-axis
$graph->xaxis->SetLabelAlign('right','center');

// Add some grace to y-axis so the bars doesn't go
// all the way to the end of the plot area
$graph->yaxis->scale->SetGrace(20);

// We don't want to display Y-axis
$graph->yaxis->Hide();

// Now create a bar pot
$bplot = new BarPlot($data);
$bplot->SetFillColor("lightgreen");
$bplot->SetFillGradient("#D4DDE2","#365F8D",GRAD_HOR);
$bplot->SetShadow();

//You can change the width of the bars if you like
//$bplot->SetWidth(0.5);

// We want to display the value of each bar at the top
$bplot->value->Show();
$bplot->value->SetFont(FF_FONT1,FS_NORMAL);
$bplot->value->SetAlign('left','center');
$bplot->value->SetColor("black","darkred");
$bplot->value->SetFormat('%d');

// Add the bar to the graph
$graph->Add($bplot);

// .. and stroke the graph
$graph->Stroke();
?>