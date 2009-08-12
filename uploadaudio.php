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
		TrimFormData($f, $s, 'name');
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} elseif (!$audio->contentid && !$_FILES['audio']) {
			error('Please select an audio file to upload');
		} else {
			//submit changes
			$audio = new AudioFile(getCurrentAudio());
			$testname = TrimFormData($f, $s, 'name');
			// Strip extra whitespace from name
			$words = explode(' ', $testname);
			$testname = '';
			foreach ($words as $word) {
				if (strlen($word) > 0) {
					$testname .= "$word ";
				}
			}

			PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name

			if (QuickQuery("select * from audiofile where userid = {$USER->id} and deleted = 0 and name = '" .
				  DBSafe($testname) . "' and id != '" . $audio->id. "'")) {
				error('This audio file name is already in use, a unique one was generated');
				$testname = TrimFormData($f, $s, 'name') . ' ' . date("F jS, Y h:i a");
				PutFormData($f, $s, 'name', $testname, 'text', 1, 50, true); // Repopulate the form/session data with the generated name
			}

			PopulateObject($f,$s,$audio,array("name", "description"));
			$audio->userid = $USER->id;
			$audio->deleted = 0;
			$audio->permanent = (GetFormData($f, $s, 'permanent') == "transient")?1:0;

			if(isset($_FILES['audio'])) {
				if (!$_FILES['audio']['name']) {
					error('There was an error reading your audio file','Please try another file');
				} else {
					$path_parts = pathinfo($_FILES['audio']['name']);
	
					$ext = isset($path_parts['extension'])?$path_parts['extension']:"wav";
					if (strlen($ext) < 1 || !in_array(strtolower($ext),array('wav','aiff','au','aif'))) {
						$ext = "wav";
					}
					$audio->recorddate = date("Y-m-d G:i:s");
	
					$source = $SETTINGS['feature']['tmp_dir'] . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . 'orig.' . $ext;
					$dest = $SETTINGS['feature']['tmp_dir'] . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . '.wav';
					if(!move_uploaded_file($_FILES['audio']['tmp_name'],$source)) {
						error('There was an error reading your audio file','Please try another file');
						@unlink($source);
						@unlink($dest);
					} else {
	
						$cmd = "sox \"$source\" -r 8000 -c 1 -s -w \"$dest\" ";
						$result = exec($cmd, $res1,$res2);
	
						if($res2 || !file_exists($dest)) {
							error('There was an error reading your audio file','Please try another file',
							'Supported formats include: .wav, .aiff, and .au');
							@unlink($source);
							@unlink($dest);
						} else {
							$contentid = contentPut($dest,"audio/wav");
	
							@unlink($source);
							@unlink($dest);
	
							if ($contentid) {
								$audio->contentid = $contentid;
								$audio->update();
								setCurrentAudio($audio->id);
	
								ClearFormData($f);
								redirect('audio.php');
							} else {
								error('There was an error uploading your audio file','Please try again',
								'Supported formats include: .wav, .aiff, and .au');
							}
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
			array("description","text",1,50),
			array("permanent","radio")
			);
	PopulateForm($f,$s,$audio,$fields);
	PutFormData($f, $s, 'permanent', ($audio->permanent)?"transient":"permanent");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = "Upload Audio File";

include_once("popup.inc.php");
NewForm($f);
buttons(submit($f, $s, $audio->contentid ? 'Save' : 'Upload'), button('Cancel', "window.location = 'audio.php';"));
startWindow("Audio Information " . help('UploadAudioFile'));

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
	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder">Auto Expire:</th>
		<td class="bottomBorder">
			<ol style="border: 0px; padding: 0px; margin: 0px; list-style-type: none;">
				<li><? NewFormItem($f, $s, 'permanent', 'radio', false, 'permanent'); ?>Yes (Keep for six months)</li>
				<li><? NewFormItem($f, $s, 'permanent', 'radio', false, 'transient'); ?>No (Keep forever)</li>
			</ol>
		</td>
	</tr>
<? if($audio->contentid) { ?>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Preview:</th>
		<td><?= button("Play", NULL,"previewaudio.php?id=" .$audio->id);?></td>
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
