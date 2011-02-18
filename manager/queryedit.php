<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("AspAdminQuery.obj.php");

if (!$MANAGERUSER->authorized("editqueries"))
	exit("Not Authorized");

$managerqueries = DBFindMany("AspAdminQuery", "from aspadminquery order by name");

$f = "editroles";
$s = "main";
$reloadform = 0;


if (CheckFormSubmit($f,$s)) {
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		
		} else if (GetFormData($f,$s,"query_new") != "" && (GetFormData($f,$s,"numargs_new") == "" || GetFormData($f,$s,"name_new") == "")) {
			error('Dont forget to fill in num args and name');
		} else {
			$uids = GetFormData($f,$s,"edituserids");
			
			foreach ($managerqueries as $id => $managerquery) {
				$managerquery->name = GetFormData($f,$s,"name_$id");
				$managerquery->notes = GetFormData($f,$s,"notes_$id");
				$managerquery->query = GetFormData($f,$s,"query_$id");
				$managerquery->numargs = GetFormData($f,$s,"numargs_$id");
				
				$managerquery->update();
			}
			
			if (GetFormData($f,$s,"name_new") != "" && GetFormData($f,$s,"query_new") != "" && GetFormData($f,$s,"numargs_new") != "") {
				$managerquery = new AspAdminQuery();
				$managerquery->name = GetFormData($f,$s,"name_new");
				$managerquery->notes = GetFormData($f,$s,"notes_new");
				$managerquery->query = GetFormData($f,$s,"query_new");
				$managerquery->numargs = GetFormData($f,$s,"numargs_new");
				
				$managerquery->create();
			}
			
			redirect();
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ) {

	ClearFormData($f);	
	
	foreach ($managerqueries as $id => $managerquery) {
		PutFormData($f,$s,"name_$id",$managerquery->name,"text",0,255,true);
		PutFormData($f,$s,"notes_$id",$managerquery->notes,"text");
		PutFormData($f,$s,"query_$id",$managerquery->query,"text","nomin","nomax",true);
		PutFormData($f,$s,"numargs_$id",$managerquery->numargs,"numeric",0,20,true);
	}
	
	PutFormData($f,$s,"name_new","","text",0,255);
	PutFormData($f,$s,"notes_new","","text");
	PutFormData($f,$s,"query_new","","text");
	PutFormData($f,$s,"numargs_new","","numeric",0,20);
}
include_once("nav.inc.php");


NewForm($f);
?>

<p>Allows writing custom queries to be run on each customer. Results will be shown in an html table or CSV download. The customer ID is prepended to each CSV row.</p>
<p>You can specify custom arguments:</p>
<ul>
<li>_$<i>n</i>_ where <i>n</i> is the argument number (starting at 1). Don't forget to update Num Args appropriately.</li>
<li>_$CUSTOMERID_ is replaced with the customer id.</li>
</ul>
<p>Be sure to include a description of your query and each argument in the notes field.</p>

<table class=list width="100%">
	<tr class="listHeader">
		<th align="left">Name</th>
		<th align="left">Notes</th>
		<th align="left">Query</th>
		<th align="left">Num Args</th>
	</tr>
<?
	$counter = 0;
	foreach ($managerqueries as $id => $managerquery) {
?>
		<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
		<td valign=top><? NewFormItem($f, $s, "name_$id", 'text', "20","255"); ?></td>
		<td><? NewFormItem($f, $s, "notes_$id", 'textarea', "30","4",'style="width:100%;"'); ?></td>
		<td><? NewFormItem($f, $s, "query_$id", 'textarea', "30","4",'style="width:100%;"'); ?></td>
		<td valign=top><? NewFormItem($f, $s, "numargs_$id", 'text', "3","2"); ?></td>
		</tr>
<?
	}
?>
	<tr><td style="border-top: 3px dotted black;" colspan=4>New:</td></tr>
	<tr>
		<td valign=top><? NewFormItem($f, $s, "name_new", 'text', "20","255"); ?></td>
		<td><? NewFormItem($f, $s, "notes_new", 'textarea', "30","4",'style="width:100%;"'); ?></td>
		<td><? NewFormItem($f, $s, "query_new", 'textarea', "30","4",'style="width:100%;"'); ?></td>
		<td valign=top><? NewFormItem($f, $s, "numargs_new", 'text', "3","2"); ?></td>
		</tr>
</table>

<? NewFormItem($f, $s, 'Save', 'submit')?>

<?
EndForm();
include_once("navbottom.inc.php");
