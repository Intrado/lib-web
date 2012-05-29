<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValJobName extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		$jobcount = QuickQuery("select count(id) from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')", false, array($USER->id, $value));
		if ($jobcount)
			return "$this->label: ". _L('There is already an active notification with this name. Please choose another.');
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	_L('Template Section 1'), // Optional
	"subject" => array(
		"label" => _L('TextField'),
		"value" => "a",
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3, "max" => 30),
			array("ValJobName","type"=> "job")
		)
	)
);


$buttons = array();
$form = new Form("broadcast",$formdata,$buttons, "vertical");

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		
		//save data here	
		error_log('Data Saved');
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("start.php");
		else
			redirect("start.php");
	}
}


// Moved from message_sender.php 

include("nav.inc.php");


?>

<script> 
	orgid = 123;
	userid = <? print_r($_SESSION['user']->id); ?>;
</script>

<div class="container cf">

	<div class="wrapper">
	
	<!-- <div class="main_activity"> -->

	<div class="window newbroadcast">
		<div class="window_title_wrap">
		<h2>New Broadcast</h2>
		<ul class="msg_steps cf">
		<li class="active"><a id="tab_1" ><span class="icon">1</span> Subject &amp; Recipients</a></li>
		<li><a id="tab_2" data-active="true"><span class="icon">2</span> Message Content</a></li>
		<li><a id="tab_3" data-active="true"><span class="icon">3</span> Review &amp; Send</a></li>
		</ul>
		</div>
		
		<div class="window_body_wrap">

		<form name="broadcast">

			<!-- ============== Message sender section 1, Subject and Recipients ============== -->
				
			<div id="msg_section_1" class="window_panel">
			
			<h3 class="flag">Broadcast Info</h3>
			<div class="field_wrapper">
				<fieldset>
				<label for="msgsndr_form_subject">Subject</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_subject" name="broadcast_subject" data-ajax="true" class="required" /> 
						<span class="error"></span>
						<p>e.g. "PTA Meeting Reminder"</p>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_form_type">Type</label>
					<div class="controls">
						<select id="msgsndr_form_type" name="type"></select>
					</div>
				</fieldset>
			</div>
			
			<h3 class="flag">Recipient Lists</h3>
			
			<div class="add_recipients">	
				<div class="add_btn">
					<button id="choose_list_button" href="#msgsndr_choose_list" data-toggle="modal">Pick from Existing Lists</button>
					or
					<button href="#msgsndr_build_list" data-toggle="modal">Build a List Using Rules</button>
				</div>
			
				<table id="msgsndr_list_info" class="info">
					<thead>
						<tr>
							<th colspan="2">List Name</th>
							<th>Count</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
							<a class="removelist" href="" title="Remove List"></a>
							<a class="savelist" href="" title="Save List"></a>
							</td>
							<td>List name goes here</td>
							<td>1000</td>
						</tr>
						<tr>
							<td>
							<a class="removelist" href="" title="Remove List"></a>
							<a class="savelist" href="" title="Save List"></a>
							</td>
							<td>List name goes here</td>
							<td>1000</td>
						</tr>
						<tr>
							<td colspan="2">Total</td>
							<td>2000</td>
						</tr>
					</tbody>
				</table>
			
				<fieldset>
					<input class="addme" type="checkbox" id="msgsndr_form_myself"/>
					<label class="addme" for="msgsndr_form_myself">Add Myself</label>
				</fieldset>

				<div id="addme" class="hide">

					<fieldset>
						<label for="msgsndr_form_mephone">Phone</label>
						<div class="controls">
							<input type="text" id="msgsndr_form_mephone" name="me_phone" />
						</div>
					</fieldset>

					<fieldset>
						<label for="msgsndr_form_meemail">Email</label>
						<div class="controls">
							<input type="text" id="msgsndr_form_meemail" name="me_email" />
						</div>
					</fieldset>

					<fieldset>
						<label for="msgsndr_form_mesms">SMS</label>
						<div class="controls">
							<input type="text" id="msgsndr_form_mesms" name="me_sms" />
						</div>
					</fieldset>

				</div><!-- #addme -->


			</div><!-- end add_recipients -->
			
			<div class="msg_confirm">
					<button class="btn_confirm" disabled="disabled" data-next="2">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->
			
			<!-- Message sender section 2, Message Content -->

			<div id="msg_section_2" class="window_panel">
			<p>Create the content for this message, or <a href="#saved_message" data-toggle="modal">load a saved message</a></p>
			
				
				
			<ul class="msg_content_nav cf">
				<li class="notactive ophone">
					<a id="msgsndr_ctrl_phone" href="#"><span class="icon"></span> Add <span>Phone</span></a>
				</li>
				<li class="notactive oemail">
					<a id="msgsndr_ctrl_email" href="#"><span class="icon"></span> Add <span>Email</span></a>
				</li>
				<li class="notactive osms">
					<a id="msgsndr_ctrl_sms" href="#"><span class="icon"></span> Add <span>SMS</span></a>
				</li>
				<li class="notactive osocial">
					<a id="msgsndr_ctrl_social" href="#"><span class="icon"></span> Add <span>Social</span></a>
				</li>
			</ul>
			
			<div class="tab_content">
				<!-- Add the phone panel -->
				<div id="msgsndr_tab_phone" class="tab_panel">
				<fieldset class="check">
					<label for="msgsndr_form_type">Switch Audio Type</label>
					<div id="switchaudio" class="controls">
						<button class="audioleft active" data-type="call-me">Call Me to Record</button><button class="audioright" href="#" data-type="text-to-speech">Text-to-Speech</button>
					</div>
				</fieldset>

				<div id="call-me" class="audio">
				<fieldset>
					<label for="msgsndr_form_number">Number to Call</label>
					<div class="controls">
					<input class="small" type="text" id="msgsndr_form_number" name="phone_number" /> <span class="error"></span>
					<button class="record" id="ctrecord"><span class="icon"></span> Call Now to Record</button>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_scratch">Scratch Pad <span>(optional)</span></label>
					<div class="controls">
					<textarea id="msgsndr_form_scratch" name="msgsndr_form_scratch"></textarea>
					<p>You can use this to write notes about what you'd like to say. This information isn't saved anywhere.</p>
					</div>
				</fieldset>
				
				<fieldset>
					<div class="controls">
						<a href="#" class="toggle-more" data-target="#advanced-opts">Advanced Options (caller ID, etc.)</a>
					</div>
				</fieldset>
				
				<div id="advanced-opts" class="close">
				<fieldset>
					<label for="msgsndr_form_callid">Caller ID</label>
					<div class="controls">
					<select id="msgsndr_form_callid" name="type">
					<option value="general">(651) 323-2003</option>
					</select>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_days">Days to Run</label>
					<div class="controls">
					<select id="msgsndr_form_days" name="type">
					<option value="1">1</option>
					<option value="2">2</option>
					<option value="3">3</option>
					<option value="4">4</option>
					<option value="5">5</option>
					</select>
					</div>
				</fieldset>
				
				<fieldset>
					<div class="controls">
					<div class="cf"><input class="addme" type="checkbox" /><label class="addme">Voice Response</label></div>
					<div class="cf"><input class="addme" type="checkbox" /><label class="addme">Call Confirmation</label></div>
					</div>
				</fieldset>
			</div><!-- #advanced-opts -->
				
				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" href="#">Save Phone Message</button>
					<button>Cancel</button>
					</div>
				</fieldset>

			</div><!-- #call-me -->

			<div id="text-to-speech" class="audio hide">

				<fieldset>
					<label for="msgsndr_tts_message">Message</label>
					<div class="controls">
						<button class="btn-small paste-from">Paste text from email</button>
						<textarea id="msgsndr_tts_message"></textarea>
						<button><span class="icon play"></span> Play Audio</button>
						<span class="tts characters">160 Characters Left</span>
					</div>
				</fieldset>

				<fieldset>
					<label for="msgsndr_form_translate">Translate</label>
					<input type="checkbox" id="msgsndr_form_translate" value=""/>
					<a class="toggle-more" href="">Show Translations</a>
				</fieldset>

				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save">Save TTS Message</button>
					<button>Cancel</button>
					</div>
				</fieldset>

			</div><!-- #text-to-speech -->

			</div><!-- #msgsndr_tab_phone -->
				
			<!-- Add the email panel -->
			<div id="msgsndr_tab_email" class="tab_panel">

				<fieldset class="check">
					<label for="msgsndr_form_name">From Name</label>
					<div class="controls">
					<input type="text" id="msgsndr_form_name" name="email_name"/> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_email">From Email</label>
					<div class="controls">
					<input type="text" id="msgsndr_form_email" name="email_address"/> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_mailsubject">Subject</label>
					<div class="controls">
					<input type="text" id="msgsndr_form_mailsubject" name="email_subject" /> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_body">Body</label>
					<div class="controls">
					<textarea id="msgsndr_form_body" name="email_body" data-ajax="true"></textarea> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_translate">Translate</label>
					<input type="checkbox" id="msgsndr_form_translate" value=""/>
					<a class="toggle-more" href="">Show Translations</a>
				</fieldset>
				
				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" href="#">Save Email Message</button>
					<button>Cancel</button>
					</div>
				</fieldset>

			</div><!-- tab_panel -->
				
			<!-- Add the sms panel -->
			<div id="msgsndr_tab_sms" class="tab_panel">

				<fieldset class="check">
					<label for="msgsndr_form_sms">SMS Text</label>
					<div class="controls">
					<textarea id="msgsndr_form_sms" name="sms_text"></textarea>
					<div>
					<p><a href="#" id="sms_sc">Spell Check</a> <span class="sms characters">160 Characters left</span></p>
					<span class="loading">loading..</span>
					</div>
					
					</div>
				</fieldset>
				
				<fieldset class="form_actions">
					<div class="controls">
						<button class="btn_save" disabled="disabled">Save SMS Message</button>
						<button>Cancel</button>
					</div>
				</fieldset>

			</div>
				
			<!-- Add the social network panel -->
			<div id="msgsndr_tab_social" class="tab_panel">
		
				<div class="social_tab">
					<fieldset class="check">
						<div class="controls">
							<input class="addme" type="checkbox" id="msgsndr_form_audio" name="msgsndr_form_audio" />
							<label class="addme" for="msgsndr_form_audio">Include a link to the audio message</label>
						</div>
					</fieldset>
				</div><!--  -->
			
				<div class="social_tab hidden" data-social="facebook">
					<fieldset class="check">
						<div class="controls">
							<input class="addme social" type="checkbox" id="msgsndr_form_facebook" name="msgsndr_form_facebook" />
							<label class="addme" for="msgsndr_form_facebook"><strong>Post to Facebook</strong></label>
						</div>
					</fieldset>

					<div class="facebook">

						<fieldset>
							<label for="msgsndr_form_fbmsg">Message</label>
							<div class="controls">
								<textarea id="msgsndr_form_fbmsg" name="facebook_message"></textarea> <span class="error"></span>
								<p><a href="#">Spell Check</a> <span class="fb characters">420 Characters left</span></p>
							</div>
						</fieldset>

					</div><!-- facebook -->
				</div><!-- data-social= facebook -->



				<div class="social_tab hidden" data-social="twitter">
					<fieldset class="check">
						<div class="controls">
							<input class="addme social" type="checkbox" id="msgsndr_form_twitter" name="msgsndr_form_twitter" />
							<label class="addme" for="msgsndr_form_twitter"><strong>Post to Twitter</strong></label>
						</div>
					</fieldset>

					<div class="twitter">

						<fieldset>
							<label for="msgsndr_form_tmsg">Message</label>
							<div class="controls">
							<textarea id="msgsndr_form_tmsg" name="twitter_message"></textarea> <span class="error"></span>
							<p><a href="#">Spell Check</a> <span class="twit characters">140 Characters left</span></p>
							</div>
						</fieldset>

					</div><!-- twitter -->
				</div><!-- data-social= twitter -->



				<div class="social_tab hidden" data-social="feed">				
					<fieldset class="check">
						<div class="controls">
							<input class="addme social" type="checkbox" id="msgsndr_form_feed" name="msgsndr_form_feed" />
							<label class="addme" for="msgsndr_form_feed"><strong>Post to Feeds</strong></label>
						</div>
					</fieldset>

					<div class="feed">

						<fieldset>
							<label for="msgsndr_form_rsstitle">Post Title</label>
							<div class="controls">
								<input type="text" id="msgsndr_form_rsstitle" name="rss_title" />
							</div>
						</fieldset>

						<fieldset>
							<label for="msgsndr_form_rssmsg">Message</label>
							<div class="controls">
							<textarea id="msgsndr_form_fbmsg" name="facebook_message"></textarea> <span class="error"></span>
							<p><a href="#">Spell Check</a></p>
							</div>
						</fieldset>

						<fieldset class="check">
							<label class="control-label" for="">Post to Feeds</label>
							<div class="controls" id="feed_categories">
							</div>
						</fieldset>

					</div><!-- rss -->
				</div><!-- data-social= feed -->

				
				<fieldset class="form_actions">
					<div class="controls">
						<button class="btn_save">Save Social Messages</button>
						<button>Cancel</button>
					</div>
				</fieldset>

			</div>

			</div><!-- end tab_content -->
			
			<div class="msg_confirm">
				<button class="btn_confirm">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->
			
			<!-- Message sender section 3, Review and Send -->

			<div id="msg_section_3" class="window_panel">
				<p><strong>Subject</strong> Holidays Reminder</p>
				<p><strong>Type</strong> General Annoucement</p>
				<p><strong>Recipients 2000</strong></p>

				<fieldset>
					<div class="controls">
					<div class="cf"><input class="addme" type="checkbox" /><label class="addme">Skip Duplicate Phones</label></div>
					</div>
				</fieldset>
			
			<div class="msg_confirm">
				<a href="#">save for later</a> or 
				<button class="btn_confirm" id="send_new_broadcast">Send Message <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->
		</form>
		
		</div><!-- /window_body_wrap -->
		
	</div><!-- endwindow newbroadcast -->
	
	<!--/div--><!-- end main_activity -->


	<div class="main_aside">
		<div class="help">
			<h3>Need Help?</h3>
			<p>Visit the <a href="">help section</a> or call (800) 920-3897. Also be sure to <a href="">give us feedback</a> about the new version.</p>
		</div>
	</div><!-- end main_aside-->
	
</div><!-- end wrapper -->
	

<!-- ============== Modal windows and mini forms ================ -->

<!-- choose list modal -->
<div id="msgsndr_choose_list" class="modal hide">
	<h3>Add existing list <a href="#" class="close" data-dismiss="modal">x</a></h3>
	<ul id="lists_list">
		<!--li><input type="checkbox"/><label>Exampke</label></li-->
	</ul>
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button><button class="btn_confirm" href="#">Add Lists</button>
	</div>
</div>

<!-- build list modal -->				
<div id="msgsndr_build_list" class="modal hide">
	<h3>Add Recipients Using Rules <a href="#" class="close" data-dismiss="modal">x</a></h3>
	<p>Use filters to match a group of entries in your Address Book</p>
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button> 
		<button class="btn_confirm">Add Lists</button>
	</div>
</div>

<!-- load saved message modal -->
<div id="msgsndr_saved_message" class="modal hide">
	<h3>Load a Saved Message <a href="#" class="close" data-dismiss="modal">x</a></h3>
	<div class="modal_content">
	<input type="text"/><input class="btn" type="submit" value="Search"/>
	<table class="messages">
		<thead>
			<tr>
			<th>Title</th>
			<th>Created</th>
			<th><img src="themes/newui/phone.png" alt=""/></th>
			<th><img src="themes/newui/email.png" alt=""/></th>
			<th><img src="themes/newui/sms.png" alt=""/></th>
			<th><img src="themes/newui/social.png" alt=""/></th>
			</tr>
		</thead>
		<tbody>
			<tr>
			<td>Valentines Dance Cancelled</td>
			<td>4/2/12</td>
			<td></td>
			<td>y</td>
			<td>y</td>
			<td></td>
			</tr>
			<tr>
			<td>Xmas Holidays Announcement</td>
			<td>13/11/11</td>
			<td>y</td>
			<td>y</td>
			<td>y</td>
			<td>y</td>
			</tr>
			<tr>
			<td>Star Wars Day Fancy Dress</td>
			<td>04/05/12</td>
			<td></td>
			<td>y</td>
			<td>y</td>
			<td>y</td>
			</tr>
		</tbody>
	</table>
	</div>
	
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button> 
		<button class="btn_confirm">Load Selected Message</button>
	</div>

</div>
	

<script src="script/jquery.1.7.2.min.js" type="text/javascript"></script>
<script src="script/jquery.json-2.3.min.js" type="text/javascript"></script>
<script src="script/bootstrap-modal.js" type="text/javascript"></script>

<link href="themes/newui/scripts/spellcheck/spellcheck.css" type="text/css" rel="stylesheet">

<script src="themes/newui/message_sender.js" type="text/javascript"></script>
<script src="themes/newui/notification_validation.js" type="text/javascript"></script>
<script src="themes/newui/scripts/spellcheck/spellcheck.js" type="text/javascript"></script>