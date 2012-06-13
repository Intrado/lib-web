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

				<input type="checkbox" class="hidden" name="has_phone" />

				<fieldset class="check">
					<label for="msgsndr_form_type">Voice Type</label>
					<input type="hidden" id="msgsndr_phonetype" name="phone_type" value="callme" />
					<div id="switchaudio" class="controls">
						<button class="audioleft active" data-type="callme">Call Me to Record</button><button class="audioright" data-type="text">Text-to-Speech</button>
					</div>
				</fieldset>
				
				<hr />

				<div id="callme" class="audio">
				<fieldset>
					<label for="msgsndr_form_number">Recording</label>
					<div class="controls">
						<input class="small required ok" type="hidden" id="msgsndr_form_number" name="phone_number" /> <span class="error"></span>
						<!-- <button class="record" id="ctrecord"><span class="icon"></span> Call Now to Record</button> -->
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_scratch">Scratch Pad <span class="light">(optional)</span></label>
					<div class="controls">
					<button class="btn-small paste-from hidden" data-textarea="msgsndr_form_scratch">Paste text from email</button>
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
					<select id="msgsndr_form_callid" name="phone_callerid">
					<!--option value="general">(651) 323-2003</option-->
					</select>
					</div>
				</fieldset>

				<fieldset class="cf">
					<label for="msgsndr_form_days">Days to run</label>
					<div class="controls">
						<select id="msgsndr_form_days" name="broadcast_daystorun">
						</select>
					</div>
				</fieldset>
				
				
				
				<fieldset>
					<div class="controls">
					<div class="cf">
						<input class="addme" type="checkbox" id="msgsndr_voice_response" name="phone_voiceresponse"/>
						<label class="addme" for="msgsndr_voice_response">Voice Response</label>
					</div>
					<div class="cf">
						<input class="addme" type="checkbox" id="msgsndr_call_confirmation" name="phone_callconfirmation"/>
						<label class="addme">Call Confirmation</label>
					</div>
					</div>
				</fieldset>
			</div><!-- #advanced-opts -->
				
				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" disabled="disabled" data-nav=".ophone">Save Phone Message</button>
					<button class="btn_cancel" data-nav=".ophone">Cancel</button>
					</div>
				</fieldset>

			</div><!-- #call-me -->

			<div id="text" class="audio hide">

				<fieldset>
					<label for="msgsndr_tts_message">Message</label>
					<div class="controls">
						<button class="btn-small paste-from hidden" data-textarea="msgsndr_tts_message">Paste text from email</button>
						<textarea id="msgsndr_tts_message" name="phone_tts" class="required"></textarea><span class="error"></span>

						<div class="hide">
							<input id="messagePhoneText_message" name="messagePhoneText_message" type="hidden" value="{&quot;gender&quot;: &quot;female&quot;, &quot;text&quot;: &quot;&quot;}"/>
						</div>

						<div class="cf">
							<input id="messagePhoneText_message-female" name="messagePhoneText_message-gender" type="radio" value="female" checked/>
							<label for="messagePhoneText_message-female">Female</label>
						</div>

						<div class="cf">
							<input id="messagePhoneText_message-male" name="messagePhoneText_message-gender" type="radio" value="male" />
							<label for="messagePhoneText_message-male">Male</label>
						</div>

						<button id="tts_play" class="playAudio" data-text="msgsndr_tts_message" data-code="en">
							<span class="icon play"></span> Play Audio
						</button>

						<!-- <span class="tts characters">10000 Characters Left</span> -->
					</div>
				</fieldset>

				<fieldset>
					<label for="msgsndr_form_phonetranslate">Translate</label>
					<input type="checkbox" id="msgsndr_form_phonetranslate" value="" data-txt="#msgsndr_tts_message" data-display="#tts_translate" />
					<a class="toggle-translations hide" data-target="#tts_translate" data-txt="#msgsndr_tts_message" href=""></a>
				</fieldset>

				<div id="tts_translate" class="close translations">
				</div>


				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" disabled="disabled" data-nav=".ophone">Save Phone Message</button>
					<button class="btn_cancel" data-nav=".ophone">Cancel</button>
					</div>
				</fieldset>


			</div><!-- #text-to-speech -->

			</div><!-- #msgsndr_tab_phone -->
				
			<!-- Add the email panel -->
			<div id="msgsndr_tab_email" class="tab_panel">

				<input type="checkbox" class="hidden" name="has_email" />

				<fieldset class="check">
					<label for="msgsndr_form_name">From Name</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_name" name="email_name" class="required" autocomplete="off" /> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_email">From Email</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_email" name="email_address" class="required" autocomplete="off" /> <span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_form_mailsubject">Subject</label> 
					<div class="controls">
						<input type="text" id="msgsndr_form_mailsubject" name="email_subject" class="required" autocomplete="off" /> <span class="error"></span>
					</div>
				</fieldset>

				<fieldset>
					<label for="msgsndr_form_attachment">Attachments</label>
					<input id="msgsndr_form_attachment" name="email_attachment" type="hidden" value="{}">
					<div class="controls" style="overflow: hidden;">
						<div id="uploadedfiles" style="display: none; "></div>
						<div id="upload_process" style="display: none; "><img src="img/ajax-loader.gif"></div>
						<iframe id="msgsndr_form_attachment_my_attach" class="attach_file" src="_emailattachment.php?formname=broadcast&amp;itemname=msgsndr_form_attachment"></iframe>
						<div class="underneathmsg cf"></div>
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
					<input type="checkbox" id="msgsndr_form_emailtranslate" name="email_translate" value=""  data-display="#email_translate" />
					<a class="toggle-translations hide" data-target="#email_translate" href=""></a>
				</fieldset>

				<div id="email_translate" class="close translations">

<!-- 					<fieldset>
							<label for="">Spanish</label>
							<input type="checkbox" />
						<div class="controls">
							<textarea disabled>Translated Text here</textarea>
						</div>
					</fieldset>
 -->
				</div>
				
				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" disabled="disabled" data-nav=".oemail">Save Email Message</button>
					<button class="btn_cancel" data-nav=".oemail">Cancel</button>
					</div>
				</fieldset>

			</div><!-- tab_panel -->
				
			<!-- Add the sms panel -->
			<div id="msgsndr_tab_sms" class="tab_panel">

				<input type="checkbox" class="hidden" name="has_sms" />

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
						<button class="btn_cancel" data-nav=".osms">Cancel</button>
					</div>
				</fieldset>

			</div>
				
			<!-- Add the social network panel -->
			<div id="msgsndr_tab_social" class="tab_panel">
		
				<div class="social_tab" id="audiolink">
					<fieldset class="check">
						<div class="controls">
							<input class="addme" type="checkbox" id="msgsndr_form_audio" name="social_audio" disabled="disabled" />
							<label class="addme" for="msgsndr_form_audio">Include a link to the audio message</label>
						</div>
					</fieldset>
				</div><!--  -->
			
				<div class="social_tab hidden" data-social="facebook">
					<fieldset class="check">
						<div class="controls">
							<input class="addme social" type="checkbox" id="msgsndr_form_facebook" name="has_facebook" />
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
						
						<fieldset> 
							<div id="msgsndr_fbpages" class="controls fb_reset">
							<input id="msgsndr_fbpage" type="hidden" value="" name="social_fbpages">
							<input id="msgsndr_fbpageauthpages" type="hidden" value="" name="social_fbpagesauthpages">
								<div id="fb-root"></div>
								
								<div id="msgsndr_fbpageconnect" class="hidden">
									<button class="btn" onclick="popup('popupfacebookauth.php', 640, 400);" type="button">
										<img class="btn_middle_icon" alt="" src="img/icons/custom/facebook.gif">
										Add Facebook Account
									</button>
								</div>
								
								<div id="msgsndr_fbpagerenew" class="hidden">
									<button class="btn" onclick="popup('popupfacebookauth.php', 640, 400);" type="button">
										<img class="btn_middle_icon" alt="" src="img/icons/custom/facebook.gif">
										Renew Facebook Authorization
									</button>
								</div>
								
								<div id="msgsndr_fbpageactionlinks" class="hidden">
									<a id="jobedit_fbpageall" class="actionlink">Select All</a>
									&nbsp;|&nbsp;
									<a id="jobedit_fbpagenone" class="actionlink">Remove All</a>
								</div>
								<div id="msgsndr_fbpagefbpages"></div>
								<div id="msgsndr_fbpagesmessage" class="underneathmsg cf"></div>
							</div>
						</fieldset>
					

					</div><!-- facebook -->
				</div><!-- data-social= facebook -->


				<div class="social_tab hidden" data-social="twitter">
					<fieldset class="check">
						<div class="controls">
							<input class="addme social" type="checkbox" id="msgsndr_form_twitter" name="has_twitter" />
							<label class="addme" for="msgsndr_form_twitter"><strong>Post to Twitter</strong></label>
						</div>
					</fieldset>

					<div class="twitter">

						<fieldset>
							<label for="msgsndr_form_tmsg">Message</label>
							<div class="controls">
							<textarea id="msgsndr_form_tmsg" name="twitter_message" class="required"></textarea> <span class="error"></span>
							<p><a href="javascript:void(null);" id="sms_sc" onclick="(new spellChecker($('msgsndr_form_tmsg')) ).openChecker();">Spell Check</a> <span class="twit characters"> Characters left</span></p>
							</div>
						</fieldset>

					</div><!-- twitter -->
				</div><!-- data-social= twitter -->



				<div class="social_tab hidden" data-social="feed">				
					<fieldset class="check">
						<div class="controls">
							<input class="addme social" type="checkbox" id="msgsndr_form_feed" name="has_feed" />
							<label class="addme" for="msgsndr_form_feed"><strong>Post to RSS</strong></label>
						</div>
					</fieldset>

					<div class="feed">

						<fieldset>
							<label for="msgsndr_form_rsstitle">Post Title</label>
							<div class="controls">
								<input type="text" id="msgsndr_form_rsstitle" name="rss_title" class="required" />
							</div>
						</fieldset>

						<fieldset>
							<label for="msgsndr_form_rssmsg">Message</label>
							<div class="controls">
							<textarea id="msgsndr_form_rssmsg" name="feed_message" class="required"></textarea> <span class="error"></span>
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
						<button class="btn_save" disabled="disabled" data-nav=".osocial">Save Social Messages</button>
						<button class="btn_cancel" data-nav=".osocial">Cancel</button>
					</div>
				</fieldset>

			</div>

			</div><!-- end tab_content -->
			
			<div class="msg_confirm">
				<button class="btn_confirm" disabled="disabled" data-next="3">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->
