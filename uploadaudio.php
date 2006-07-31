<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("inc/content.inc.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Content.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if($_GET['id'])
	setCurrentAudio($_GET['id']);

$audio = new AudioFile(getCurrentAudio());

/****************** main message section ******************/

$f = "audio";
$s = "upload";
$reloadform = 0;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} elseif (!$audio->contentid && !$_FILES['audio']) {
			error('Please select an audio file to upload');
		} else {
			//submit changes
			$audio = new AudioFile(getCurrentAudio());
			$testname = DBSafe(GetFormData($f, $s, 'name'));
			// Strip extra whitespace from name
			$words = explode(' ', $testname);
			$testname = '';
			foreach ($words as $word) {
				if (strlen($word) > 0) {
					$testname .= "$word ";
				}
			}
			$testname = rtrim($testname); // Remove trailing space
			PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name

			if (QuickQuery("select * from audiofile where userid = {$USER->id} and deleted = 0 and name = '" .
				  $testname . "' and id != '" . $audio->id. "'")) {
				error('This audio file name is already in use, a unique one was generated');
				$testname = DBSafe(GetFormData($f, $s, 'name')) . ' ' . date("F jS, Y h:i a");
				PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name
			}

			PopulateObject($f,$s,$audio,array("name", "description"));
			$audio->userid = $USER->id;
			$audio->deleted = 0;

			if($_FILES['audio']) {
				$path_parts = pathinfo($_FILES['audio']['name']);

				$ext = $path_parts['extension'];

				if (strlen($ext) < 1) {
					$ext = "wav";
				}

				$audio->recorddate = date("Y-m-d H:i:s");

				$source = getcwd() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . 'orig.' . $ext;
				$dest = getcwd() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . '.wav';
				if(!move_uploaded_file($_FILES['audio']['tmp_name'],$source)) {
					error('There was an error reading your audio file','Please try another file');
					@unlink($source);
					@unlink($dest);
				} else {

					$cmd = "sox \"$source\" -r 8000 -c 1 -s -w \"$dest\" ";
					$result = exec($cmd, $res1,$res2);

					if($res2 || !file_exists($dest)) {
						error('There was an error reading your audio file','Please try another file');
						@unlink($source);
						@unlink($dest);
					} else {
						if ($IS_COMMSUITE) {

							$content = new Content();
							$content->data = base64_encode(file_get_contents($dest));
							$content->contenttype = "audio/wav";
							$content->update();
							$contentid = $content->id;
						} else {
							$contentid = contentPut($dest,"audio/wav");
						}

						@unlink($source);
						@unlink($dest);

						if ($contentid) {
							$audio->contentid = $contentid;
							$audio->update();
							setCurrentAudio($audio->id);

							ClearFormData($f);
							redirect('audio.php');
						} else {
							error('There was an error uploading your audio file','Please try again');
						}
					}
				}
			} else {
				$audio->update();
				ClearFormData($f);
				redirect('audio.php');
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	$audio = new AudioFile(getCurrentAudio());
	$fields = array(
			array("name","text",1,50,true),
			array("description","text",1,50)
			);
	PopulateForm($f,$s,$audio,$fields);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Upload Audio File";

include_once("popup.inc.php");
NewForm($f);
buttons(submit($f, $s, 'save', $audio->contentid ? 'save' : 'upload'), button('cancel', "window.location = 'audio.php';"));
startWindow("Audio Information " . help('UploadAudioFile', null, 'blue'));

if (false) {
?>
<script language="javascript">
var sel = opener.document.getElementById('audio');
if(sel && <? print ($audio->id && $audio->name) ? 'true' : 'false'; ?>) {
	var id = <? print $audio->id; ?>;
	var index = -1;
	for(var i = 0; i < sel.options.length; i++) {
		if(sel.options[i].value == id) {
			sel.selectedIndex = index = i;
		}
	}
	if(index == -1) {
		var opt = document.createElement('OPTION');
		opt.text = '<? print addslashes($audio->name); ?>';
		opt.value = <? print $audio->id; ?>;
		sel.options.add(opt);
		sel.selectedIndex = sel.options.length - 1;
	} else {
		sel.options[index].text = '<? print addslashes($audio->name); ?>';
	}
}
</script>
<?
}
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder">Name:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, 'name', 'text'); ?></td>
	</tr>
	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder">Description:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, 'description', 'text'); ?></td>
	</tr>
<? if($audio->contentid) { ?>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Preview:</th>
		<td><?= button("play", NULL,"previewaudio.php?id=" .$audio->id);?></td>
	</tr>
<? } else { ?>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Upload File:</th>
		<td><input type="file" name="audio"></td>
	</tr>
<? } ?>
</table>
<?
endWindow();
buttons();
EndForm();
include_once("popupbottom.inc.php");
?>
