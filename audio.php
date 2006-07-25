<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Content.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize("sendphone")) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$id = DBSafe($_GET['delete']);
	if (userOwns("audiofile",$id)) {
		$job = new AudioFile($id);
		$job->deleted = 1;
		$job->update();
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////
function fmt_actions($obj, $name) {
	return "<a href=\"uploadaudio.php?id=$obj->id\">Edit</a>&nbsp;|&nbsp;<a href=\"?delete=$obj->id\" onclick=\"return confirmDelete();\">Delete</a>";
}

function fmt_preview($obj, $name) {
	return button("play", NULL,"previewaudio.php?id=" .$obj->id);


	//"<a href=\"previewaudio.php?id=$obj->id\"><img border=\"0\" src=\"audio.gif\"></a>";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Audio File Library";

include_once("popup.inc.php");

button_bar(button('done', 'window.close()'), ($USER->authorize("starteasy") ? button('callmetorecord', NULL,"callme.php?origin=audio") . help('AudioFileEditor_CallMeToRecord') : ''), button('uploadaudio', NULL,"uploadaudio.php?id=new") . help('AudioFileEditor_UploadAudioFile'));

$data = DBFindMany("AudioFile",", name + 0 as dummy from audiofile where userid=$USER->id and not deleted order by dummy,name");
$titles = array(	"preview" => "Preview",
					"name" => "Name",
					"description" => "Description",
					"recorddate" => "Created",
					"Actions" => "Actions"
					);

startWindow('Audio Files', 'padding: 3px;');
showObjects($data, $titles, array("preview" => "fmt_preview", "created" => "fmt_date", "Actions" => "fmt_actions", 'method' => 'fmt_ucfirst'));
endWindow();
print('<br>');
print button('done', 'window.close();');

include_once("popupbottom.inc.php");
