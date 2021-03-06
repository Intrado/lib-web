<?

function output_bar_graph($bars, $labels, $w = 500, $h = 400)
{
	// New graph with a drop shadow
	if ($w == 0) {
		$w = count($labels) * 60;
	}
	$graph = new Graph($w,$h,'auto');
	$graph->img->SetMargin(100,60,20,130);

	foreach ($bars as $bar) {
		$graph->Add($bar);
	}

	// Use a "text" X-scale
	$graph->SetScale("textlin");
	$graph->xaxis->SetTickLabels($labels);
	$graph->xaxis->HideTicks();
	$graph->xaxis->SetPos("min");
	$graph->xaxis->SetLabelAngle(90);
	$graph->xaxis->SetFont(FF_ARIAL);

	$graph->yaxis->Hide();
	$graph->yaxis->SetTextLabelInterval(2);
	$graph->yaxis->HideFirstTickLabel();
	$graph->yaxis->SetFont(FF_ARIAL);
	$graph->SetFrame(false);

	// Use built in font
	$graph->title->SetFont(FF_ARIAL,FS_BOLD);

	$graph->title->SetFont(FF_ARIAL,FS_BOLD);
	$graph->yaxis->title->SetFont(FF_ARIAL,FS_BOLD);
	$graph->xaxis->title->SetFont(FF_ARIAL,FS_BOLD);

	// Finally output the  image
	$graph->Stroke();
}

function output_pie_graph($data, $legend, $colors, $title, $w = 400, $h = 300) {
	//check for no data and display a message
	if (count($data) == 0 || array_sum($data) == 0) {
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

	$graph = new PieGraph($w,$h,"auto");
	//$graph->SetShadow();
	$graph->SetFrame(false);
	$graph->SetAntiAliasing();

	$graph->title->Set($title);

	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	$p1 = new PiePlot3D(($data));

	$p1->SetLabelType(PIE_VALUE_ABS);
	$p1->value->SetFormat('');
	$p1->value->Show();
	$size = 0.375;
	$p1->SetSize($size);
	$p1->SetCenter(0.3);
	$p1->SetLegends(($legend));
	$p1->SetSliceColors($colors);

	$graph->Add($p1);
	$graph->Stroke();
}


function output_simple_pie_graph($data, $colors, $w = 300, $h = 300) {
	//check for no data and display a message
	if (count($data) == 0 || array_sum($data) == 0) {
		$graph = new CanvasGraph($w,$h,"auto");
		$graph->Stroke();
		exit();
	}

	$graph = new PieGraph($w,$h,"auto");
	$graph->SetShadow();
	$graph->SetFrame(false);
	$graph->SetAntiAliasing(true);

	$p1 = new PiePlot(($data));

	$p1->SetLabelType(PIE_VALUE_ABS);
	$p1->value->SetFormat('');
	$p1->SetGuideLines(false);
	$p1->value->Show();

	$p1->ExplodeAll(($w+$h)/200);
	$p1->SetSize(0.45);
	
	$p1->SetSliceColors($colors);

	$graph->Add($p1);
	$graph->Stroke();
}

function output_ring_pie_graph_with_legend($centerLabel, $data, $colors, $legends, $size) {
	//check for no data and display a gray graph
	$valueColor = "#333333";
	$hideLegend = false;
	if (count($data) == 0 || array_sum($data) == 0) {
		$data[0] = 1;
		$colors = array();
		foreach ($data as $d) {
			$colors[] = "#dddddd";
		}
		$valueColor = "#dddddd";
		$hideLegend = false;
	}

	$height=250;
	$width=475;
	$fontSize=10;
	$fontStype=FS_BOLD;

	switch ($size) {
		case "large":
			$height=250;
			$width=475;
			$fontSize=10;
			break;
		case "medium":
			$height=166;
			$width=316;
			$fontSize=8;
			break;
		case "small":
			$height=96;
			$width=182;
			$fontSize=5;
			$fontStype=FS_NORMAL;
			break;
		default:
			$height=250;
			$width=475;
			$fontSize=10;
			break;
	}

	$graph = new PieGraph($width,$height,"auto");
	$graph->SetShadow();
	$graph->SetFrame(false);
	$graph->SetAntiAliasing(true);

	$p1 = new PiePlotC($data);

	$p1->SetLabelType(PIE_VALUE_PER);
	$p1->SetGuideLines(false);
	$p1->value->Show();

	//$p1->ExplodeAll(($w+$h)/200);
	$p1->ExplodeAll(2);
	$p1->SetSize(0.45);

	$p1->value->SetFont(FF_ARIAL,FS_BOLD,$fontSize);
	$p1->value->SetColor($valueColor);
	$p1->value->HideZero(true);
	$p1->SetLabelPos(0.66);

	$p1->SetCenter(0.28);

	$p1->SetLegends($legends);
	$graph->legend->SetShadow(false);
	$graph->legend->SetFillColor("white");
	$graph->legend->SetColor($valueColor);
	$graph->legend->SetFrameWeight(0);
	$graph->legend->SetFont(FF_ARIAL, FS_BOLD,$fontSize);
	$graph->legend->SetAbsPos($height, $height / 3, 'left', 'center');
	$graph->legend->Hide($hideLegend);

	$p1->SetSliceColors($colors);

	$p1->SetMidColor("#FFFFff");
	$p1->SetMidSize(0.4);
	$p1->midtitle->Set($centerLabel);
	$p1->midtitle->SetWordWrap(10);
	$p1->midtitle->SetFont(FF_ARIAL,FS_BOLD,$fontSize);
	$p1->midtitle->SetColor("#333333");

	$graph->Add($p1);
	$graph->Stroke();
}
?>