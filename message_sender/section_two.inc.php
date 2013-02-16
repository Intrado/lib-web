			<!-- ============== Message sender section 2, Message Content ============== -->

			<div id="msg_section_2" class="window_panel hide">
			<p>Create the content for this message<span id="load-a-saved-message" class="hide">, or <a id="load_saved_message" href="#msgsndr_saved_message" data-toggle="modal">load a saved message</a></span></p>
			
			<div id="msgsndr_loaded_message" style="display:none;">
				<input type="hidden" id="loaded_message_id" name="message_loaded" value="" />
				<p>Message content loaded from saved message: <span id="loaded_message_name" class=""></span></p>
			</div>

			<ul class="msg_content_nav cf">
				<li class="notactive ophone">
					<button id="msgsndr_ctrl_phone"><span class="icon"></span> Add <span>Phone</span></button>
				</li>
				<li class="notactive oemail">
					<button id="msgsndr_ctrl_email"><span class="icon"></span> Add <span>Email</span></button>
				</li>
				<li class="notactive osms">
					<button id="msgsndr_ctrl_sms"><span class="icon"></span> Add <span>SMS</span></button>
				</li>
				<li class="notactive osocial">
					<button id="msgsndr_ctrl_social"><span class="icon"></span> Add <span>Social</span></button>
				</li>
			</ul>
			
			<div class="tab_content">
				<!-- Add the phone panel -->
				<div id="msgsndr_tab_phone" class="tab_panel">

				<input type="checkbox" class="hidden" id="msgsndr_hasphone" name="msgsndr_hasphone" />

				<fieldset class="check">
					<label for="msgsndr_phonemessagetype">Voice Type&nbsp;<img id="msgsndr_phonemessagetype_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<input type="hidden" id="msgsndr_phonemessagetype" name="msgsndr_phonemessagetype" value="callme" />
					<div id="switchaudio" class="controls">
						<button class="audioleft active" data-type="callme">Call Me to Record</button><button class="audioright" data-type="text">Text-to-Speech</button>
						<div id="msgsndr_phonemessagetype_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
				
				<hr />

				<div id="callme" class="audio">
				<fieldset>
					<label for="msgsndr_phonemessagecallme">Recording&nbsp;<img id="msgsndr_phonemessagecallme_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<input class="msgdata" type="hidden" id="msgsndr_phonemessagecallme" name="msgsndr_phonemessagecallme" />
						<div id="msgsndr_phonemessagecallme_msg" class="box_validatorerror er" style="display:none"></div>
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
				
				<div id="callme_advanced_options">
				<div class="phone_advanced_options">
				<fieldset>
					<div class="controls">
						<a href="#" class="toggle-more" data-target="#advanced-opts">Advanced Options (caller ID, etc.)</a>
					</div>
				</fieldset>
				
				<div id="advanced-opts" class="close">
				<fieldset>
					<label for="msgsndr_optioncallerid">Caller ID&nbsp;<img id="msgsndr_optioncallerid_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<select id="optioncallerid_select"></select>
						<input type="text" id="msgsndr_optioncallerid" name="msgsndr_optioncallerid"/>
						<div id="msgsndr_optioncallerid_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>

				<fieldset class="cf">
					<label for="msgsndr_optionmaxjobdays">Days to run&nbsp;<img id="msgsndr_optionmaxjobdays_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<select id="msgsndr_optionmaxjobdays" name="msgsndr_optionmaxjobdays">
						</select>
						<div id="msgsndr_optionmaxjobdays_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
				
				<fieldset class="hide">
					<label for="msgsndr_optionleavemessage"><img id="msgsndr_optionleavemessage_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls cf">
						<input class="addme" type="checkbox" id="msgsndr_optionleavemessage" name="msgsndr_optionleavemessage"/>
						<label class="addme" for="msgsndr_optionleavemessage">Voice Response</label>
						<div id="msgsndr_optionleavemessage_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
				
				<fieldset class="hide">
					<label for="msgsndr_optionmessageconfirmation"><img id="msgsndr_optionmessageconfirmation_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls cf">
						<input class="addme" type="checkbox" id="msgsndr_optionmessageconfirmation" name="msgsndr_optionmessageconfirmation"/>
						<label class="addme" for="msgsndr_optionmessageconfirmation">Call Confirmation</label>
						<div id="msgsndr_optionmessageconfirmation_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
			</div><!-- #advanced-opts -->
			</div>
			</div>
				
				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" disabled="disabled" data-nav=".ophone">Save Phone Message</button>
					<button class="btn_discard" data-nav=".ophone">Discard</button>
					</div>
				</fieldset>

			</div><!-- #call-me -->

			<div id="text" class="audio hide">

				<fieldset>
					<label for="msgsndr_tts_message">Message&nbsp;<img id="msgsndr_phonemessagetext_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<input type="hidden" id="msgsndr_phonemessagetext" name="msgsndr_phonemessagetext" class="msgdata"/>
						<button class="btn-small paste-from hidden" data-textarea="msgsndr_tts_message">Paste text from email</button>
						<textarea id="msgsndr_tts_message" name="phone_tts" class="required msgdata" maxlength="10000"></textarea><span class="error"></span>
						<div id="msgsndr_phonemessagetext_msg" class="box_validatorerror er" style="display:none"></div>
						
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
					</div>
				</fieldset>

				<fieldset class="hide">
					<label for="msgsndr_phonemessagetexttranslate">Translate&nbsp;<img id="msgsndr_phonemessagetexttranslate_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<input type="checkbox" id="msgsndr_phonemessagetexttranslate" class="msgdata" value="tts_translate" name="msgsndr_phonemessagetexttranslate" data-txt="#msgsndr_tts_message" data-display="#tts_translate" />
					<a class="toggle-translations hide" data-target="#tts_translate" href=""></a>
					<div id="msgsndr_phonemessagetexttranslate_msg" class="box_validatorerror er" style="display:none"></div>
				</fieldset>

				<div id="tts_translate" class="close translations">
				</div>

				<div id="text_advanced_options">

				</div>


				<fieldset class="form_actions">
					<div class="controls">
					<button class="btn_save" disabled="disabled" data-nav=".ophone" data-tts="true">Save Phone Message </button><img src="img/ajax-loader.gif" class="loading hide">
					<img name="valspinner" class="hidden" src="img/ajax-loader.gif">
					<button class="btn_discard" data-nav=".ophone">Discard</button>
					</div>
				</fieldset>


			</div><!-- #text-to-speech -->

			</div><!-- #msgsndr_tab_phone -->
				
			<!-- Add the email panel -->
			<div id="msgsndr_tab_email" class="tab_panel">

				<input type="checkbox" class="hidden msgdata" id="msgsndr_hasemail" name="msgsndr_hasemail"/>
				<div id="msgsndr_hasemail_msg" class="box_validatorerror er" style="display:none"></div>

				<fieldset class="check">
					<label for="msgsndr_emailmessagefromname">From Name&nbsp;<img id="msgsndr_emailmessagefromname_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<input type="text" id="msgsndr_emailmessagefromname" name="msgsndr_emailmessagefromname" class="required msgdata" autocomplete="off" maxlength="50"/> <span class="error"></span>
						<div id="msgsndr_emailmessagefromname_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_emailmessagefromemail">From Email&nbsp;<img id="msgsndr_emailmessagefromemail_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<input type="text" id="msgsndr_emailmessagefromemail" name="msgsndr_emailmessagefromemail" class="required msgdata" autocomplete="off" maxlength="255"/>
						<div id="msgsndr_emailmessagefromemail_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
				
				<fieldset>
					<label for="msgsndr_emailmessagesubject">Subject&nbsp;<img id="msgsndr_emailmessagesubject_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label> 
					<div class="controls">
						<input type="text" id="msgsndr_emailmessagesubject" name="msgsndr_emailmessagesubject" class="required msgdata" autocomplete="off" maxlength="255"/> <span class="error"></span>
						<div id="msgsndr_emailmessagesubject_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>

				<fieldset>
					<label for="msgsndr_emailmessageattachment">Attachments&nbsp;<img id="msgsndr_emailmessageattachment_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<input id="msgsndr_emailmessageattachment" name="msgsndr_emailmessageattachment" class="box_validator msgdata" type="hidden" value="{}">
					<div class="controls" style="overflow: hidden;">
						<div id="uploadedfiles" class="msgdata" style="display: none; "></div>
						<div id="upload_process" style="display: none; "><img src="img/ajax-loader.gif"></div>
						<iframe id="msgsndr_form_attachment_my_attach" class="attach_file" src="_emailattachment.php?formname=broadcast&amp;itemname=msgsndr_emailmessageattachment"></iframe>
						<div id="msgsndr_emailmessageattachment_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
				<div id="stationery_email_view">
					<fieldset id="stationeryfield" class="stationeryselector">
						<legend>Email Stationery:</legend>
						<div id="stationeryselector" class="radiobox stationeryselector">
					
						</div>
					</fieldset>
					<fieldset id="stationerypreviewfield" class="stationerypreview">
						<legend>Email Stationery Preview:</legend>
						<iframe id="stationerypreview"  src="mgstationeryview.php?preview"></iframe>
					</fieldset>
					<div class="cf"></div>
					<fieldset class="form_actions">
						<div class="controls">
							<button id="msgsndr_emailstationerycontinue" class="btn_select" disabled="disabled">Use Stationery</button><img src="img/ajax-loader.gif" class="loading hide" />
							<button id="msgsndr_emailnostationery">Skip Stationery</button>
							<button id="msgsndr_emailcancelstationery" class="btn_cancel" data-nav=".oemail">Cancel</button>
						</div>
					</fieldset>
				</div>
				
				<div id="main_email_view">
				<fieldset>
					<label for="msgsndr_emailmessagetext">Body&nbsp;<img id="msgsndr_emailmessagetext_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						
						<button id="paste_from_tts" class="paste-from" data-textarea="msgsndr_tts_message" disabled="disabled">Paste text from Phone</button>
						<div class="cf"></div>
						<div id="rcieditor_scratch" style="display: none;"></div>
						<textarea id="msgsndr_emailmessagetext" name="msgsndr_emailmessagetext" class="required msgdata hide" data-ajax="true"></textarea>
						<div id="msgsndr_emailmessagetext_msg" class="box_validatorerror er" style="display:none"></div>
						<div id="msgsndr_emailmessagetext-htmleditor"></div>
					</div>
				</fieldset>

				<fieldset>
					<!-- <label for="msgsndr_previewemail">Preivew </label> -->
					<div class="controls">
						<button id="msgsndr_previewemail">Preview Email</button>
					</div>
				</fieldset>
				
				<fieldset class="hide">
					<label for="msgsndr_emailmessagetexttranslate">Translate&nbsp;<img id="msgsndr_emailmessagetexttranslate_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<input type="checkbox" id="msgsndr_emailmessagetexttranslate" class="msgdata" name="msgsndr_emailmessagetexttranslate" value="email_translate"  data-display="#email_translate" />
						<a class="toggle-translations hide" data-target="#email_translate" data-txt="#msgsndr_tts_message" href=""></a>
						<div id="msgsndr_emailmessagetexttranslate_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
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
					<button class="btn_save" disabled="disabled" data-nav=".oemail">Save Email Message</button><img src="img/ajax-loader.gif" class="loading hide" />
					<img name="valspinner" class="hidden" src="img/ajax-loader.gif">
					<button class="btn_discard" data-nav=".oemail">Discard</button>
					</div>
				</fieldset>

				</div>
			</div><!-- tab_panel -->
				
			<!-- Add the sms panel -->
			<div id="msgsndr_tab_sms" class="tab_panel">

				<input type="checkbox" class="hidden" id="msgsndr_hassms" name="msgsndr_hassms"/>
				<div id="msgsndr_hassms_msg" class="box_validatorerror er" style="display:none"></div>

				<fieldset class="check">
					<label for="msgsndr_smsmessagetext">SMS Text&nbsp;<img id="msgsndr_smsmessagetext_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<textarea id="msgsndr_smsmessagetext" name="msgsndr_smsmessagetext" class="msgdata required"></textarea>
						<div id="msgsndr_smsmessagetext_msg" class="box_validatorerror er" style="display:none"></div>
						<div>
							<p><a href="javascript:void(null);" id="sms_sc" onclick="(new spellChecker($('msgsndr_smsmessagetext')) ).openChecker();">Spell Check</a> <span class="sms characters">160 Characters left</span></p>
						</div>
					</div>
				</fieldset>
				
				<fieldset class="form_actions">
					<div class="controls">
						<button class="btn_save" disabled="disabled" data-nav=".osms">Save SMS Message</button>
						<img name="valspinner" class="hidden" src="img/ajax-loader.gif">
						<button class="btn_discard" data-nav=".osms">Discard</button>
					</div>
				</fieldset>

			</div>
				
			<!-- Add the social network panel -->
			<div id="msgsndr_tab_social" class="tab_panel">
		
				<div class="social_tab hidden" id="audiolink">
					<fieldset class="check">
						<div class="controls">
							<input class="addme msgdata" type="checkbox" id="msgsndr_phonemessagepost" name="msgsndr_phonemessagepost" />
							<label class="addme" for="msgsndr_phonemessagepost"><img id="msgsndr_phonemessagepost_icon" class="formicon" src="img/pixel.gif" title="" alt="">&nbsp;Include a link to the audio message</label>
							<div id="msgsndr_phonemessagepost_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>
				</div><!--  -->
			
				<div class="social_tab hidden" data-social="facebook">
					<fieldset class="check">
						<div class="controls cf fbicon">
							<input class="msgdata addme social" type="checkbox" id="msgsndr_hasfacebook" name="msgsndr_hasfacebook" />
							<label class="addme" for="msgsndr_hasfacebook"><img id="msgsndr_hasfacebook_icon" class="formicon" src="img/pixel.gif" title="" alt="">&nbsp;<strong>Post to Facebook</strong></label>
							<div id="msgsndr_hasfacebook_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>

					<div class="facebook">

						<fieldset>
							<label for="msgsndr_socialmediafacebookmessage">Message&nbsp;<img id="msgsndr_socialmediafacebookmessage_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
							<div class="controls">
								<textarea id="msgsndr_socialmediafacebookmessage" name="msgsndr_socialmediafacebookmessage" class="msgdata required" ></textarea>
								<div id="msgsndr_socialmediafacebookmessage_msg" class="box_validatorerror er" style="display:none"></div>
								<div><p><a href="javascript:void(null);" id="fb_sc" onclick="(new spellChecker($('msgsndr_socialmediafacebookmessage')) ).openChecker();">Spell Check</a> <span class="fb characters">420 Characters left</span></p></div>
							</div>
						</fieldset>

						
						<fieldset> 
							<label for="msgsndr_socialmediafacebookpage">Post to&nbsp;<img id="msgsndr_socialmediafacebookpage_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
							<div id="msgsndr_socialmediafacebookpages" class="controls fb_reset">
								<input id="msgsndr_socialmediafacebookpage" type="hidden" value="" name="msgsndr_socialmediafacebookpage">
								<input id="msgsndr_socialmediafacebookpageauthpages" type="hidden" value="">
								<div id="fb-root"></div>
								
								<div id="msgsndr_socialmediafacebookpageconnect" class="hidden">
									<button class="btn" onclick="popup('popupfacebookauth.php', 640, 400);" type="button">
										<img class="btn_middle_icon" alt="" src="img/icons/custom/facebook.gif">
										Add Facebook Account
									</button>
								</div>
								
								<div id="msgsndr_socialmediafacebookpagerenew" class="hidden">
									<button class="btn" onclick="popup('popupfacebookauth.php', 640, 400);" type="button">
										<img class="btn_middle_icon" alt="" src="img/icons/custom/facebook.gif">
										Renew Facebook Authorization
									</button>
								</div>
								
								<div id="msgsndr_socialmediafacebookpageactionlinks" class="hidden actionlinks">
									<a id="msgsndr_socialmediafacebookpageall" class="actionlink">Select All</a>
									<a id="msgsndr_socialmediafacebookpagenone" class="actionlink">Remove All</a>
								</div>
								<div id="msgsndr_socialmediafacebookpagefbpages" class="hidden fbpagelist"></div>
								<div id="msgsndr_socialmediafacebookpagesmessage" class="underneathmsg cf"></div>
								<div id="msgsndr_socialmediafacebookpage_msg" class="box_validatorerror er" style="display:none"></div>
							</div>
						</fieldset>
					

					</div><!-- facebook -->
				</div><!-- data-social= facebook -->


				<div class="social_tab hidden" data-social="twitter">
					<fieldset class="check">
						<div class="controls cf twiticon">
							<input class="msgdata addme social" type="checkbox" id="msgsndr_hastwitter" name="msgsndr_hastwitter" />
							<label class="addme" for="msgsndr_hastwitter"><img id="msgsndr_hastwitter_icon" class="formicon" src="img/pixel.gif" title="" alt="">&nbsp;<strong>Post to Twitter</strong></label>
							<div id="msgsndr_hastwitter_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>

					<div class="twitter">

						<fieldset>
							<p id="msgsndr_twittername" class="twittername"></p>
							<label for="msgsndr_socialmediatwittermessage">Message&nbsp;<img id="msgsndr_socialmediatwittermessage_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
							<div class="controls">
								<textarea id="msgsndr_socialmediatwittermessage" name="msgsndr_socialmediatwittermessage" class="msgdata required" ></textarea>
								<div id="msgsndr_socialmediatwittermessage_msg" class="box_validatorerror er" style="display:none"></div>
								<div><p><a href="javascript:void(null);" id="tw_sc" onclick="(new spellChecker($('msgsndr_socialmediatwittermessage')) ).openChecker();">Spell Check</a> <span class="twit characters"> Characters left</span></p></div>
							</div>
						</fieldset>

					</div><!-- twitter -->
				</div><!-- data-social= twitter -->



				<div class="social_tab hidden" data-social="feed">				
					<fieldset class="check">
						<div class="controls cf rssicon">
							<input class="msgdata addme social" type="checkbox" id="msgsndr_hasfeed" name="msgsndr_hasfeed" />
							<label class="addme" for="msgsndr_hasfeed"><img id="msgsndr_hasfeed_icon" class="formicon" src="img/pixel.gif" title="" alt="">&nbsp;<strong>Post to RSS</strong></label>
							<div id="msgsndr_hasfeed_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>

					<div class="feed">
						<input type="hidden" id="msgsndr_socialmediafeedmessage" name="msgsndr_socialmediafeedmessage" class="" value="" />

						<fieldset>
							<label for="msgsndr_form_rsstitle">Post Title&nbsp;<img id="msgsndr_socialmediafeedmessage_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
							<div class="controls">
								<input type="text" id="msgsndr_form_rsstitle" name="rss_title" class="msgdata required" maxlength="30"/>
							</div>
						</fieldset>

						<fieldset>
							<label for="msgsndr_form_rssmsg">Message</label>
							<div class="controls">
							<textarea id="msgsndr_form_rssmsg" name="feed_message" class="msgdata required"></textarea> <span class="error"></span>
							<div id="msgsndr_socialmediafeedmessage_msg" class="box_validatorerror er" style="display:none"></div>
							<p><a href="javascript:void(null);" id="rss_sc" onclick="(new spellChecker($('msgsndr_form_rssmsg')) ).openChecker();">Spell Check</a></p>
							</div>
						</fieldset>
						
						<fieldset class="check">
							<label class="control-label" for="feed_categories">Post to Feeds&nbsp;<img id="msgsndr_socialmediafeedcategory_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
							<div class="controls multicheckbox" id="msgsndr_socialmediafeedcategory" name="msgsndr_socialmediafeedcategory"></div>
							<div id="msgsndr_socialmediafeedcategory_msg" class="box_validatorerror er" style="display:none"></div>
						</fieldset>

					</div><!-- rss -->
				</div><!-- data-social= feed -->

				
				<fieldset class="form_actions">
					<div class="controls">
						<button class="btn_save" disabled="disabled" data-nav=".osocial">Save Social Messages</button>
						<img name="valspinner" class="hidden" src="img/ajax-loader.gif">
						<button class="btn_discard" data-nav=".osocial">Discard</button>
					</div>
				</fieldset>

			</div>

			</div><!-- end tab_content -->
			
			<div class="msg_confirm">
				<img name="valspinner" class="hidden" src="img/ajax-loader.gif"><button class="btn_confirm" disabled="disabled" data-next="3">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->
