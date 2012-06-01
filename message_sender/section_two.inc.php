			<!-- ============== Message sender section 2, Message Content ============== -->

			<div id="msg_section_2" class="window_panel">
			<p>Create the content for this message, or <a href="#msgsndr_saved_message" data-toggle="modal">load a saved message</a></p>
			
			<div id="msgsndr_loaded_message" style="display:none;">
				<input type="hidden" id="loaded_message_id" name="message_loaded" value="" />
				<p>Message content loaded from saved message: <span id="loaded_message_name" class=""></span></p>
			</div>

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

				<input type="hidden" name="has_phone" value="" />

				<fieldset class="check">
					<label for="msgsndr_form_type">Switch Audio Type</label>
					<div id="switchaudio" class="controls">
						<button class="audioleft active" data-type="call-me">Call Me to Record</button><button class="audioright" data-type="text-to-speech">Text-to-Speech</button>
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
					<button class="btn_save" disabled="disabled">Save Phone Message</button>
					<button>Cancel</button>
					</div>
				</fieldset>

			</div><!-- #call-me -->

			<div id="text-to-speech" class="audio hide">

				<fieldset>
					<label for="msgsndr_tts_message">Message</label>
					<div class="controls">
						<button class="btn-small paste-from hidden" id="paste_from_email">Paste text from email</button>
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
					<button class="btn_save" disabled="disabled">Save TTS Message</button>
					<button>Cancel</button>
					</div>
				</fieldset>

			</div><!-- #text-to-speech -->

			</div><!-- #msgsndr_tab_phone -->
				
			<!-- Add the email panel -->
			<div id="msgsndr_tab_email" class="tab_panel">

				<input type="hidden" name="has_email" value="" />

				<fieldset class="check">
					<label for="msgsndr_form_name">From Name</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_name" name="email_name" class="required" /> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_email">From Email</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_email" name="email_address" class="required" /> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_mailsubject">Subject</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_mailsubject" name="email_subject" class="required" /> <span class="error"></span>
					</div>
				</fieldset>

				<fieldset>
					<label for="msgsndr_form_attachment">Attachments</label>
					<input id="msgsndr_form_attachment" name="msgsndr_form_attachment" type="hidden" value="{}">
					<div class="controls" style="overflow: hidden;">
						<div id="uploadedfiles" style="display: none; "></div>
						<div id="upload_process" style="display: none; "><img src="img/ajax-loader.gif"></div>
						<iframe id="msgsndr_form_attachment_my_attach" class="attach_file" src="_emailattachment.php?formname=broadcast&amp;itemname=msgsndr_form_attachment"></iframe>
					</div>
				</fieldset>
				
				<fieldset>
					<!--a id="editor_basic" href="javascript:void(null);">basic</a> | <a id="editor_advanced" href="javascript:void(null);">advanced</a-->
					<label for="msgsndr_form_body">Body</label>
					<div class="controls">
					<textarea id="msgsndr_form_body" name="email_body" data-ajax="true"></textarea><span id="emailBodyError" class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_translate">Translate</label>
					<input type="checkbox" id="msgsndr_form_translate" value=""/>
					<a class="toggle-more" href="">Show Translations</a>
				</fieldset>
				
				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" disabled="disabled"  data-nav=".oemail">Save Email Message</button>
					<button>Cancel</button>
					</div>
				</fieldset>

			</div><!-- tab_panel -->
				
			<!-- Add the sms panel -->
			<div id="msgsndr_tab_sms" class="tab_panel">

				<input type="hidden" name="has_sms" value="" />

				<fieldset class="check">
					<label for="msgsndr_form_sms">SMS Text</label>
					<div class="controls">
					<textarea id="msgsndr_form_sms" name="sms_text"></textarea>
					<div>
					<p><a href="javascript:void(null);" id="sms_sc" onclick="(new spellChecker($('msgsndr_form_sms')) ).openChecker();">Spell Check</a> <span class="sms characters">160 Characters left</span></p>
					</div>
					
					</div>
				</fieldset>
				
				<fieldset class="form_actions">
					<div class="controls">
						<button class="btn_save" disabled="disabled" data-nav=".osms">Save SMS Message</button>
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
								<textarea id="msgsndr_form_fbmsg" name="facebook_message" class="required"></textarea> <span class="error"></span>
								<p><a href="javascript:void(null);" id="sms_sc" onclick="(new spellChecker($('msgsndr_form_fbmsg')) ).openChecker();">Spell Check</a> <span class="fb characters">420 Characters left</span></p>
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
							<p><a href="javascript:void(null);" id="sms_sc" onclick="(new spellChecker($('msgsndr_form_tmsg')) ).openChecker();">Spell Check</a> <span class="twit characters"> Characters left</span></p>
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
						<button class="btn_save" disabled="disabled">Save Social Messages</button>
						<button>Cancel</button>
					</div>
				</fieldset>

			</div>

			</div><!-- end tab_content -->
			
			<div class="msg_confirm">
				<button class="btn_confirm" disabled="disabled" data-next="3">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->