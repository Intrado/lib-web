<?
//remember to embed the $SESSIONID manually in each task element
//when you are done with the session, set $SESSIONDATA to null

//$SESSIONDATA = array_merge($SESSIONDATA,$BFXML_VARS);



if ($REQUEST_TYPE == "new") {


} else if ($REQUEST_TYPE == "continue") {


} else if ($REQUEST_TYPE == "result") {
	//huh, they must have hung up
	$SESSIONDATA = null;
?>
	<ok />
<?
}



?>