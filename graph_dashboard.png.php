<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");

include_once ("jpgraph/jpgraph.php");
include_once ("jpgraph/jpgraph_pie.php");
include_once ("jpgraph/jpgraph_pie3d.php");
include_once ("jpgraph/jpgraph_canvas.php");

require_once('inc/graph.inc.php');

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point


$templatecolors = array(
	"green" => "#55aa28",
	"red" => "#e84f1f",
	"orange" => "#fc9524",
	"blue" => "#48a3be"
);


$data = array();
$colors = array();

$fields = array("blue","red","orange","green");


foreach ($fields as $field) {
	if (isset($_GET[$field])) {
		$data[$field] = $_GET[$field];
		$colors[$field] = $templatecolors[$field];
	}
}

output_simple_pie_graph(array_values($data), array_values($colors),100,100);

?>