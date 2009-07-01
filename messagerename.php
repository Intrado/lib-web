<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Message.obj.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Voice.obj.php");
include_once("obj/FieldMap.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (! ($USER->authorize("sendemail") || $USER->authorize("sendphone") || $USER->authorize("sendprint") || $USER->authorize('sendsms'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentMessage($_GET['id']);
	redirect("messagerename.php");
}

/****************** main message section ******************/

$form = "message";
$section = "mainrename";
$reloadform = 0;

if(CheckFormSubmit($form,$section))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($form))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($form, $section);


		$message = new Message($_SESSION['messageid']);
		//get the ID of any message with the same name and type
		$name = trim(GetFormData($form,$section,"name"));
		if ( empty($name) ) {
			PutFormData($form,$section,"name",'',"text",1,50,true);
		}
		
		$existsid = QuickQuery("select id from message where name='" . DBSafe($name) . "' and type='$message->type' and userid='$USER->id' and deleted=0");

		//do check
		if( CheckFormSection($form, $section) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($existsid && $existsid != $_SESSION['messageid']) {
			error('A message named \'' . $name . '\' already exists');
		} else {

			$message->name = $name;
			$message->description = trim(GetFormData($form,$section,"description"));
			
			$message->update();
			ClearFormData($form);
			redirect('messages.php');
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($form);

	$message = new Message($_SESSION['messageid']);

	$fields = array(
			array("name","text",0,50,true),
			array("description","text",0,50)
			);
	PopulateForm($form,$section,$message,$fields);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:messages";
$TITLE = 'Rename Message: ' . escapehtml(trim(GetFormData($form,$section,"name")));

include_once("nav.inc.php");

NewForm($form);
buttons(submit($form, $section, 'Save'));
startWindow('Message Information', 'padding: 3px;');
print 'Name: ';
NewFormItem($form,$section,"name","text", 30,50);
print '&nbsp;&nbsp;Description: ';
NewFormItem($form,$section,"description","text", 30,50);
print '&nbsp;';
endWindow();

buttons();
EndForm();
include_once("navbottom.inc.php");
?>