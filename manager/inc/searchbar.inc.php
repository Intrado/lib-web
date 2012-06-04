<?
$getLocation = mb_strpos($_SERVER['REQUEST_URI'],"?");
$posturl = $getLocation !== false ? substr($_SERVER['REQUEST_URI'], 0,$getLocation) : $_SERVER['REQUEST_URI'];

?>

<script>
//check on timeout after keyup
//clear error flag
//on timeout 250

function setcontent (response, obj) {
	var html = response.responseText;
	// no search results
	if (html == " ") { 
		obj.innerHTML = "";
		return;
	}
	show(obj.id);
	obj.innerHTML = html;
}

function submitform (name) {
	// if blank, don't submit.
	if ($('searchvalue').value.replace(/^[ ]+/, '') == '') {
		$('searchpreview').innerHTML = "";
		return;
	}
	var request = '<?=$posturl?>?ajax=true&search=' + $('searchvalue').value;
	cachedAjaxGet(request,setcontent,$('searchpreview'));
}

function keyuptimer (e, t, ignoreenterkey, fn, args) {
	if (this.timeoutid)
		clearTimeout(this.timeoutid);
	var e=window.event || e;
	var keyunicode=e.charCode || e.keyCode;
	if (keyunicode != 13 || !ignoreenterkey)
		this.timeoutid = setTimeout(fn,t,args);
}
</script>

<form id="search" autocomplete="off" action="<?=$posturl?>" method="get">
	<? if (isset($_GET["showdisabled"]))
		print "<input type='hidden' name='showdisabled' value='1'/>";
	?>
	<input id="searchvalue" name="search" type="text" onkeyup="keyuptimer(event, 300, true, submitform, 'searchvalue');" size="30" value="<?=isset($_GET["search"]) ? escapehtml($_GET["search"]) : ""?>"/><button type="submit">Search</button> Search ID, URL
	<div id="searchpreview">
	</div>
</form>