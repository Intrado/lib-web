<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("obj/Validator.obj.php");
?>



<div class="window messagescreen cf">

	<div class="window_title_wrap">
	<h3 class="window_title">New Notification</h3>
	<ul class="msg_steps cf">
	<li><a id="tab1" class="" href=""><span class="icon">1</span> Subject &amp; Recipients</a></li>
	<li><a id="tab2" class="" href=""><span class="icon">2</span> Message Content</a></li>
	<li><a id="tab3" class="" href=""><span class="icon">3</span> Review &amp; Send</a></li>
	</ul>
	</div>
	
	<div id="msg_section_1" class="window_panel">
	
	<h3 class="flag">Notification Info</h3>
	
	<form>
	
	<fieldset>
	<label for="form_subject">Subject</label>
		<div class="controls">
		<input type="text" id="form_subject" name="subject"/>
		<p>e.g. "PTA Meeting Reminder"</p>
		</div>
	</fieldset>
	
	<fieldset class="cf">
		<label for="form_type">Type</label>
		<div class="controls">
		<select id="form_type" name="type">
			<option value="general">General Announcement</option>
			<option value="attend">Attendance</option>
			<option value="emergency">Emergency</option>
		</select>
		</div>
	</fieldset>
	</form>
	
	<h3 class="flag">Recipient Lists</h3>
	
	<div class="add_recipients">	
		<div class="add_btn">
		<a class="btn" href="#choose_list" data-toggle="modal">Pick from Existing Lists</a>
		or
		<a class="btn" href="#build_list" data-toggle="modal">Build a List Using Rules</a>
		</div>
		
		<div id="choose_list" class="modal hide">
			<h3>Add existing list <a href="#" class="close" data-dismiss="modal">x</a></h3>
			<ul>
				<li><input type="checkbox"/><label>Grandparents</label></li>
				<li><input type="checkbox"/><label>First Year Students</label></li>
				<li><input type="checkbox"/><label>Second Year Students</label></li>
				<li><input type="checkbox"/><label>Teachers</label></li>
			</ul>
			<div class="msg_confirm"><a class="btn" href="#" data-dismiss="modal">Cancel</a> <a class="btn btn_confirm" href="#">Add Lists</a></div>
		</div>
		
		<div id="build_list" class="modal hide">
			<h3>Add Recipients Using Rules <a href="#" class="close" data-dismiss="modal">x</a></h3>
			<p>Use filters to match a group of entries in your Address Book</p>
			<div class="msg_confirm"><a class="btn" href="#" data-dismiss="modal">Cancel</a> <a class="btn btn_confirm" href="#">Add Lists</a></div>
		</div>
	
		<table class="recipient_lists">
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
	
		<form class="cf">
		<input class="addme" type="checkbox" id="form_myself"/><label class="addme" for="form_myself">Add Myself</label>
		</form>
	</div><!-- end add_recipients -->
	
	<div class="msg_confirm"><a class="btn btn_confirm" href="#">Continue <span class="icon"></span></a></div>
	
	</div><!-- end window_panel -->
	
	
	<div id="msg_section_2" class="window_panel">
	<p>Create the content for this message, or <a href="#saved_message" data-toggle="modal">load a saved message</a></p>
	
		<div id="saved_message" class="modal hide">
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
					<td>19/3/12</td>
					<td></td>
					<td>y</td>
					<td>y</td>
					<td>y</td>
					</tr>
				</tbody>
			</table>
			</div>
			<div class="msg_confirm"><a class="btn" href="#" data-dismiss="modal">Cancel</a> <a class="btn btn_confirm" href="#">Load Selected Message</a></div>
		</div>
		
	<ul class="msg_content_nav cf">
	<li><a id="ctrl_phone" href="#"><span class="icon"></span> Add <span>Phone</span></a></li>
	<li><a id="ctrl_email" href="#"><span class="icon"></span> Add <span>Email</span></a></li>
	<li><a id="ctrl_sms" href="#"><span class="icon"></span> Add <span>SMS</span></a></li>
	<li><a id="ctrl_social" href="#"><span class="icon"></span> Add <span>Social</span></a></li>
	</ul>
	
	<div class="tab_content">
		<!-- Add the phone panel -->
		<div id="tab_phone" class="tab_panel">
		<form>
		<fieldset class="check">
			<label for="form_type">Switch Audio Type</label>
			<div class="controls">
			<a class="btn audioleft" href="#">Call Me to Record</a><a class="btn audioright" href="#">Text-to-Speech</a>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_number">Number to Call</label>
			<div class="controls">
			<input class="small" type="text" id="form_number" name="form_number"/>
			<a class="btn record" href="#"><span class="icon"></span> Call Now to Record</a>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_scratch">Scratch Pad <span>(optional)</span></label>
			<div class="controls">
			<textarea id="form_scratch" name="form_scratch"></textarea>
			<p>You can use this to write notes about what you'd like to say. This information isn't saved anywhere.</p>
			</div>
		</fieldset>
		
		<fieldset>
			<div class="controls">
			<a href="#">Advanced Options (caller ID, etc.)</a>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_callid">Caller ID</label>
			<div class="controls">
			<select id="form_callid" name="type">
			<option value="general">(651) 323-2003</option>
			</select>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_days">Days to Run</label>
			<div class="controls">
			<select id="form_days" name="type">
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
			<div class="cf"><input class="addme" type="checkbox" /><label class="addme">Skip Duplicate Phones</label></div>
			</div>
		</fieldset>
		
		<fieldset class="form_actions">
			<div class="controls">
			<a class="btn btn_save" href="#">Save Phone Message</a>
			<a class="btn" href="#">Cancel</a>
			</div>
		</fieldset>
		</form>
		</div>
		
		<!-- Add the email panel -->
		<div id="tab_email" class="tab_panel">
		<form>
		<fieldset>
			<label for="form_name">From Name</label>
			<div class="controls">
			<input type="text" id="form_name" name="from_name"/>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_email">From Email</label>
			<div class="controls">
			<input type="text" id="form_email" name="from_email"/>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_mailsubject">Subject</label>
			<div class="controls">
			<input type="text" id="form_mailsubject" name="form_mailsubject"/>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_body">Body</label>
			<div class="controls">
			<textarea id="form_body" name="form_body"></textarea>
			</div>
		</fieldset>
		
		<fieldset>
			<label for="form_translate">Translate</label>
			<input type="checkbox" id="form_translate" value=""/>
			<a href="">Show Translations</a>
		</fieldset>
		
		<fieldset class="form_actions">
			<div class="controls">
			<a class="btn btn_save" href="#">Save Email Message</a>
			<a class="btn" href="#">Cancel</a>
			</div>
		</fieldset>
		</form>
		</div>
		
		<!-- Add the sms panel -->
		<div id="tab_sms" class="tab_panel">
		<form>
		<fieldset>
			<label for="form_sms">SMS Text</label>
			<div class="controls">
			<textarea id="form_sms" name="form_sms"></textarea>
			<p><a href="#">Spell Check</a> <span>160 Characters left</span></p>
			</div>
		</fieldset>
		
		<fieldset class="form_actions">
			<div class="controls">
			<a class="btn btn_save" href="#">Save SMS Message</a>
			<a class="btn" href="#">Cancel</a>
			</div>
		</fieldset>
		</form>
		</div>
		
		<!-- Add the social network panel -->
		<div id="tab_social" class="tab_panel">
		<form>
		<fieldset class="check">
			<div class="controls">
			<input class="addme" type="checkbox" id="form_audio" name="form_audio" />
			<label class="addme" for="form_audio">Include a link to the audio message</label>
			</div>
		</fieldset>
		
		<fieldset class="check">
			<div class="controls">
			<input class="addme" type="checkbox" id="form_facebook" name="form_facebook" />
			<label class="addme" for="form_facebook"><strong>Post to Facebook</strong></label>
			</div>
		</fieldset>
		
		<fieldset class="check">
			<div class="controls">
			<input class="addme" type="checkbox" id="form_twitter" name="form_twitter" />
			<label class="addme" for="form_twitter"><strong>Post to Twitter</strong></label>
			</div>
		</fieldset>
		
		<fieldset class="checklast">
			<div class="controls">
			<input class="addme" type="checkbox" id="form_feed" name="form_feed" />
			<label class="addme" for="form_feed"><strong>Post to Feeds</strong></label>
			</div>
		</fieldset>
		
		<fieldset class="form_actions">
			<div class="controls">
			<a class="btn btn_save" href="#">Save Social Messages</a>
			<a class="btn" href="#">Cancel</a>
			</div>
		</fieldset>
		</form>
		</div>
	</div><!-- end tab_content -->
	
	<div class="msg_confirm"><a class="btn btn_confirm" href="#">Continue <span class="icon"></span></a></div>
	
	</div><!-- end window_panel -->
	
	
	<div id="msg_section_3" class="window_panel">
	<p><strong>Subject</strong> Holidays Reminder</p>
	<p><strong>Type</strong> General Annoucement</p>
	<p><strong>Recipients 2000</strong></p>
	
	<div class="msg_confirm"><a href="#">save for later</a> or <a class="btn btn_confirm" href="#">Send Message <span class="icon"></span></a></div>
	</div><!-- end window_panel -->

</div><!-- end window messagescreen-->

<script src="script/jquery.1.7.2.min.js"></script>
<script src="script/bootstrap-modal.js"></script>
<script src="themes/newui/message_sender.js"></script>

<!-- Optional Load Custom Form Validators --> 
<script type="text/javascript">
<? Validator::load_validators(array()); ?>
</script>

