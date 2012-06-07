
			
			<!-- ============== Message sender section 3, Review and Send ============== -->

			<div id="msg_section_3" class="window_panel">
			
			<h3 class="flag">Review Message</h3>			
			<div class="field_wrapper">
				<fieldset class="review_subject">
					<label>Subject</label>
					<p></p>
				</fieldset>
				
				<fieldset class="review_type">
					<label>Type</label>
					<p></p>
				</fieldset>
				
				<fieldset class="review_count">
					<label>Recipients</label>
					<p></p>
				</fieldset>
				
				<fieldset>
					<label>Message</label>
					<div class="controls">
						<ul class="msg_complete cf">
							<li class="notactive ophone">
								<a id="msgsndr_review_phone" href="#"><span class="icon"></span> Phone</a>
							</li>
							<li class="notactive oemail">
								<a id="msgsndr_review_email" href="#"><span class="icon"></span> Email</a>
							</li>
							<li class="notactive osms">
								<a id="msgsndr_review_sms" href="#"><span class="icon"></span> SMS</a>
							</li>
							<li class="notactive osocial">
								<a id="msgsndr_review_social" href="#"><span class="icon"></span> Social</a>
							</li>
						</ul>
					</div>
				</fieldset>
				
				<fieldset>
					<div class="controls">
						<a href="#" class="toggle-more" data-target="#adv_options">Advanced Options</a>
					</div>
				</fieldset>
				
				<div id="adv_options" class="close">
				<fieldset>
					<label for="auto_report">Auto Report</label>
					<div class="controls cf">
						<input type="checkbox" id="auto_report" name="options_autoreport"/>
					</div>
				</fieldset>

<!-- 				<fieldset>
				<label for="allow_reply">Allow Reply</label>
					<div class="controls cf">
						<input type="checkbox" id="allow_reply" name="options_allowreply"/>
					</div>	
				</fieldset> -->

				<fieldset>
					<label for="skip_phones">Skip Duplicate Phones</label>
					<div class="controls cf">
						<input type="checkbox" id="skip_phones" name="options_skipduplicates"/>
					</div>
				</fieldset>

				<fieldset>				
					<label for="save_later">Save for later</label>
					<div class="controls cf">
						<input type="checkbox" id="save_later" name="save_later" name="options_savemessage" />
						<input type="text" name="options_savemessagename" />
					</div>
				</fieldset>

<!-- 				<fieldset>
					<label for="max_attempts">Max Attempts</label>
					<div class="controls cf">
						<select id="msgsndr_form_maxattempts" name="max_attempts" name="options_maxattempts"></select>
					</div>
				</fieldset> -->

				</div><!-- adv_options -->
			</div><!-- field_wrapper -->
			
			
			<div id="schedule_options" class="close">
			<h3 class="flag">Schedule Message</h3>			
			<div class="field_wrapper">
				<fieldset class="cf">
					<label for="scheduledate">Start Date</label>
					<div class="controls">
						<input type="text" id="scheduledate" name="broadcast_scheduledate" data-ajax="true" />
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="schedulecallearly">Start Time</label>
					<div class="controls">
						<select id="schedulecallearly" name="broadcast_schedulecallearly" data-ajax="true">
							<option value="12:00 am"  >12:00 am</option>
							<option value="12:05 am"  >12:05 am</option>
							<option value="12:10 am"  >12:10 am</option>
							<option value="12:15 am"  >12:15 am</option>
							<option value="12:20 am"  >12:20 am</option>
							<option value="12:25 am"  >12:25 am</option>
							<option value="12:30 am"  >12:30 am</option>
							<option value="12:35 am"  >12:35 am</option>
							<option value="12:40 am"  >12:40 am</option>
							<option value="12:45 am"  >12:45 am</option>
							<option value="12:50 am"  >12:50 am</option>
							<option value="12:55 am"  >12:55 am</option>
							<option value="1:00 am"  >1:00 am</option>
							<option value="1:05 am"  >1:05 am</option>
							<option value="1:10 am"  >1:10 am</option>
							<option value="1:15 am"  >1:15 am</option>
							<option value="1:20 am"  >1:20 am</option>
							<option value="1:25 am"  >1:25 am</option>
							<option value="1:30 am"  >1:30 am</option>
							<option value="1:35 am"  >1:35 am</option>
							<option value="1:40 am"  >1:40 am</option>
							<option value="1:45 am"  >1:45 am</option>
							<option value="1:50 am"  >1:50 am</option>
							<option value="1:55 am"  >1:55 am</option>
							<option value="2:00 am"  >2:00 am</option>
							<option value="2:05 am"  >2:05 am</option>
							<option value="2:10 am"  >2:10 am</option>
							<option value="2:15 am"  >2:15 am</option>
							<option value="2:20 am"  >2:20 am</option>
							<option value="2:25 am"  >2:25 am</option>
							<option value="2:30 am"  >2:30 am</option>
							<option value="2:35 am"  >2:35 am</option>
							<option value="2:40 am"  >2:40 am</option>
							<option value="2:45 am"  >2:45 am</option>
							<option value="2:50 am"  >2:50 am</option>
							<option value="2:55 am"  >2:55 am</option>
							<option value="3:00 am"  >3:00 am</option>
							<option value="3:05 am"  >3:05 am</option>
							<option value="3:10 am"  >3:10 am</option>
							<option value="3:15 am"  >3:15 am</option>
							<option value="3:20 am"  >3:20 am</option>
							<option value="3:25 am"  >3:25 am</option>
							<option value="3:30 am"  >3:30 am</option>
							<option value="3:35 am"  >3:35 am</option>
							<option value="3:40 am"  >3:40 am</option>
							<option value="3:45 am"  >3:45 am</option>
							<option value="3:50 am"  >3:50 am</option>
							<option value="3:55 am"  >3:55 am</option>
							<option value="4:00 am"  >4:00 am</option>
							<option value="4:05 am"  >4:05 am</option>
							<option value="4:10 am"  >4:10 am</option>
							<option value="4:15 am"  >4:15 am</option>
							<option value="4:20 am"  >4:20 am</option>
							<option value="4:25 am"  >4:25 am</option>
							<option value="4:30 am"  >4:30 am</option>
							<option value="4:35 am"  >4:35 am</option>
							<option value="4:40 am"  >4:40 am</option>
							<option value="4:45 am"  >4:45 am</option>
							<option value="4:50 am"  >4:50 am</option>
							<option value="4:55 am"  >4:55 am</option>
							<option value="5:00 am"  >5:00 am</option>
							<option value="5:05 am"  >5:05 am</option>
							<option value="5:10 am"  >5:10 am</option>
							<option value="5:15 am"  >5:15 am</option>
							<option value="5:20 am"  >5:20 am</option>
							<option value="5:25 am"  >5:25 am</option>
							<option value="5:30 am"  >5:30 am</option>
							<option value="5:35 am"  >5:35 am</option>
							<option value="5:40 am"  >5:40 am</option>
							<option value="5:45 am"  >5:45 am</option>
							<option value="5:50 am"  >5:50 am</option>
							<option value="5:55 am"  >5:55 am</option>
							<option value="6:00 am"  >6:00 am</option>
							<option value="6:05 am"  >6:05 am</option>
							<option value="6:10 am"  >6:10 am</option>
							<option value="6:15 am"  >6:15 am</option>
							<option value="6:20 am"  >6:20 am</option>
							<option value="6:25 am"  >6:25 am</option>
							<option value="6:30 am"  >6:30 am</option>
							<option value="6:35 am"  >6:35 am</option>
							<option value="6:40 am"  >6:40 am</option>
							<option value="6:45 am"  >6:45 am</option>
							<option value="6:50 am"  >6:50 am</option>
							<option value="6:55 am"  >6:55 am</option>
							<option value="7:00 am"  >7:00 am</option>
							<option value="7:05 am"  >7:05 am</option>
							<option value="7:10 am"  >7:10 am</option>
							<option value="7:15 am"  >7:15 am</option>
							<option value="7:20 am"  >7:20 am</option>
							<option value="7:25 am"  >7:25 am</option>
							<option value="7:30 am"  >7:30 am</option>
							<option value="7:35 am"  >7:35 am</option>
							<option value="7:40 am"  >7:40 am</option>
							<option value="7:45 am"  >7:45 am</option>
							<option value="7:50 am"  >7:50 am</option>
							<option value="7:55 am"  >7:55 am</option>
							<option value="8:00 am"  >8:00 am</option>
							<option value="8:05 am"  >8:05 am</option>
							<option value="8:10 am"  >8:10 am</option>
							<option value="8:15 am"  >8:15 am</option>
							<option value="8:20 am"  >8:20 am</option>
							<option value="8:25 am"  >8:25 am</option>
							<option value="8:30 am"  >8:30 am</option>
							<option value="8:35 am"  >8:35 am</option>
							<option value="8:40 am"  >8:40 am</option>
							<option value="8:45 am"  >8:45 am</option>
							<option value="8:50 am"  >8:50 am</option>
							<option value="8:55 am"  >8:55 am</option>
							<option value="9:00 am"  >9:00 am</option>
							<option value="9:05 am"  >9:05 am</option>
							<option value="9:10 am"  >9:10 am</option>
							<option value="9:15 am"  >9:15 am</option>
							<option value="9:20 am"  >9:20 am</option>
							<option value="9:25 am"  >9:25 am</option>
							<option value="9:30 am"  >9:30 am</option>
							<option value="9:35 am"  >9:35 am</option>
							<option value="9:40 am"  >9:40 am</option>
							<option value="9:45 am"  >9:45 am</option>
							<option value="9:50 am"  >9:50 am</option>
							<option value="9:55 am"  >9:55 am</option>
							<option value="10:00 am"  >10:00 am</option>
							<option value="10:05 am"  >10:05 am</option>
							<option value="10:10 am"  >10:10 am</option>
							<option value="10:15 am"  >10:15 am</option>
							<option value="10:20 am"  >10:20 am</option>
							<option value="10:25 am"  >10:25 am</option>
							<option value="10:30 am"  >10:30 am</option>
							<option value="10:35 am"  >10:35 am</option>
							<option value="10:40 am"  >10:40 am</option>
							<option value="10:45 am"  >10:45 am</option>
							<option value="10:50 am"  >10:50 am</option>
							<option value="10:55 am"  >10:55 am</option>
							<option value="11:00 am"  >11:00 am</option>
							<option value="11:05 am"  >11:05 am</option>
							<option value="11:10 am"  >11:10 am</option>
							<option value="11:15 am"  >11:15 am</option>
							<option value="11:20 am"  >11:20 am</option>
							<option value="11:25 am"  >11:25 am</option>
							<option value="11:30 am"  >11:30 am</option>
							<option value="11:35 am"  >11:35 am</option>
							<option value="11:40 am"  >11:40 am</option>
							<option value="11:45 am"  >11:45 am</option>
							<option value="11:50 am"  >11:50 am</option>
							<option value="11:55 am"  >11:55 am</option>
							<option value="12:00 pm"  >12:00 pm</option>
							<option value="12:05 pm"  >12:05 pm</option>
							<option value="12:10 pm"  >12:10 pm</option>
							<option value="12:15 pm"  >12:15 pm</option>
							<option value="12:20 pm"  >12:20 pm</option>
							<option value="12:25 pm"  >12:25 pm</option>
							<option value="12:30 pm"  >12:30 pm</option>
							<option value="12:35 pm"  >12:35 pm</option>
							<option value="12:40 pm"  >12:40 pm</option>
							<option value="12:45 pm"  >12:45 pm</option>
							<option value="12:50 pm"  >12:50 pm</option>
							<option value="12:55 pm"  >12:55 pm</option>
							<option value="1:00 pm"  >1:00 pm</option>
							<option value="1:05 pm"  >1:05 pm</option>
							<option value="1:10 pm"  >1:10 pm</option>
							<option value="1:15 pm"  >1:15 pm</option>
							<option value="1:20 pm"  >1:20 pm</option>
							<option value="1:25 pm"  >1:25 pm</option>
							<option value="1:30 pm"  >1:30 pm</option>
							<option value="1:35 pm"  >1:35 pm</option>
							<option value="1:40 pm"  >1:40 pm</option>
							<option value="1:45 pm"  >1:45 pm</option>
							<option value="1:50 pm"  >1:50 pm</option>
							<option value="1:55 pm"  >1:55 pm</option>
							<option value="2:00 pm"  >2:00 pm</option>
							<option value="2:05 pm"  >2:05 pm</option>
							<option value="2:10 pm"  >2:10 pm</option>
							<option value="2:15 pm"  >2:15 pm</option>
							<option value="2:20 pm"  >2:20 pm</option>
							<option value="2:25 pm"  >2:25 pm</option>
							<option value="2:30 pm"  >2:30 pm</option>
							<option value="2:35 pm"  >2:35 pm</option>
							<option value="2:40 pm"  >2:40 pm</option>
							<option value="2:45 pm"  >2:45 pm</option>
							<option value="2:50 pm"  >2:50 pm</option>
							<option value="2:55 pm"  >2:55 pm</option>
							<option value="3:00 pm"  >3:00 pm</option>
							<option value="3:05 pm"  >3:05 pm</option>
							<option value="3:10 pm"  >3:10 pm</option>
							<option value="3:15 pm"  >3:15 pm</option>
							<option value="3:20 pm"  >3:20 pm</option>
							<option value="3:25 pm"  >3:25 pm</option>
							<option value="3:30 pm"  >3:30 pm</option>
							<option value="3:35 pm"  >3:35 pm</option>
							<option value="3:40 pm"  >3:40 pm</option>
							<option value="3:45 pm"  >3:45 pm</option>
							<option value="3:50 pm"  >3:50 pm</option>
							<option value="3:55 pm"  >3:55 pm</option>
							<option value="4:00 pm"  >4:00 pm</option>
							<option value="4:05 pm"  >4:05 pm</option>
							<option value="4:10 pm"  >4:10 pm</option>
							<option value="4:15 pm"  >4:15 pm</option>
							<option value="4:20 pm"  >4:20 pm</option>
							<option value="4:25 pm"  >4:25 pm</option>
							<option value="4:30 pm"  >4:30 pm</option>
							<option value="4:35 pm"  >4:35 pm</option>
							<option value="4:40 pm"  >4:40 pm</option>
							<option value="4:45 pm"  >4:45 pm</option>
							<option value="4:50 pm"  >4:50 pm</option>
							<option value="4:55 pm"  >4:55 pm</option>
							<option value="5:00 pm"  >5:00 pm</option>
							<option value="5:05 pm"  >5:05 pm</option>
							<option value="5:10 pm"  >5:10 pm</option>
							<option value="5:15 pm"  >5:15 pm</option>
							<option value="5:20 pm"  >5:20 pm</option>
							<option value="5:25 pm"  >5:25 pm</option>
							<option value="5:30 pm"  >5:30 pm</option>
							<option value="5:35 pm"  >5:35 pm</option>
							<option value="5:40 pm"  >5:40 pm</option>
							<option value="5:45 pm"  >5:45 pm</option>
							<option value="5:50 pm"  >5:50 pm</option>
							<option value="5:55 pm"  >5:55 pm</option>
							<option value="6:00 pm"  >6:00 pm</option>
							<option value="6:05 pm"  >6:05 pm</option>
							<option value="6:10 pm"  >6:10 pm</option>
							<option value="6:15 pm"  >6:15 pm</option>
							<option value="6:20 pm"  >6:20 pm</option>
							<option value="6:25 pm"  >6:25 pm</option>
							<option value="6:30 pm"  >6:30 pm</option>
							<option value="6:35 pm"  >6:35 pm</option>
							<option value="6:40 pm"  >6:40 pm</option>
							<option value="6:45 pm"  >6:45 pm</option>
							<option value="6:50 pm"  >6:50 pm</option>
							<option value="6:55 pm"  >6:55 pm</option>
							<option value="7:00 pm"  >7:00 pm</option>
							<option value="7:05 pm"  >7:05 pm</option>
							<option value="7:10 pm"  >7:10 pm</option>
							<option value="7:15 pm"  >7:15 pm</option>
							<option value="7:20 pm"  >7:20 pm</option>
							<option value="7:25 pm"  >7:25 pm</option>
							<option value="7:30 pm"  >7:30 pm</option>
							<option value="7:35 pm"  >7:35 pm</option>
							<option value="7:40 pm"  >7:40 pm</option>
							<option value="7:45 pm"  >7:45 pm</option>
							<option value="7:50 pm"  >7:50 pm</option>
							<option value="7:55 pm"  >7:55 pm</option>
							<option value="8:00 pm"  >8:00 pm</option>
							<option value="8:05 pm"  >8:05 pm</option>
							<option value="8:10 pm"  >8:10 pm</option>
							<option value="8:15 pm"  >8:15 pm</option>
							<option value="8:20 pm"  >8:20 pm</option>
							<option value="8:25 pm"  >8:25 pm</option>
							<option value="8:30 pm"  >8:30 pm</option>
							<option value="8:35 pm"  >8:35 pm</option>
							<option value="8:40 pm"  >8:40 pm</option>
							<option value="8:45 pm"  >8:45 pm</option>
							<option value="8:50 pm"  >8:50 pm</option>
							<option value="8:55 pm"  >8:55 pm</option>
							<option value="9:00 pm"  >9:00 pm</option>
							<option value="9:05 pm"  >9:05 pm</option>
							<option value="9:10 pm"  >9:10 pm</option>
							<option value="9:15 pm"  >9:15 pm</option>
							<option value="9:20 pm"  >9:20 pm</option>
							<option value="9:25 pm"  >9:25 pm</option>
							<option value="9:30 pm"  >9:30 pm</option>
							<option value="9:35 pm"  >9:35 pm</option>
							<option value="9:40 pm"  >9:40 pm</option>
							<option value="9:45 pm"  >9:45 pm</option>
							<option value="9:50 pm"  >9:50 pm</option>
							<option value="9:55 pm"  >9:55 pm</option>
							<option value="10:00 pm"  >10:00 pm</option>
							<option value="10:05 pm"  >10:05 pm</option>
							<option value="10:10 pm"  >10:10 pm</option>
							<option value="10:15 pm"  >10:15 pm</option>
							<option value="10:20 pm"  >10:20 pm</option>
							<option value="10:25 pm"  >10:25 pm</option>
							<option value="10:30 pm"  >10:30 pm</option>
							<option value="10:35 pm"  >10:35 pm</option>
							<option value="10:40 pm"  >10:40 pm</option>
							<option value="10:45 pm"  >10:45 pm</option>
							<option value="10:50 pm"  >10:50 pm</option>
							<option value="10:55 pm"  >10:55 pm</option>
							<option value="11:00 pm"  >11:00 pm</option>
							<option value="11:05 pm"  >11:05 pm</option>
							<option value="11:10 pm"  >11:10 pm</option>
							<option value="11:15 pm"  >11:15 pm</option>
							<option value="11:20 pm"  >11:20 pm</option>
							<option value="11:25 pm"  >11:25 pm</option>
							<option value="11:30 pm"  >11:30 pm</option>
							<option value="11:35 pm"  >11:35 pm</option>
							<option value="11:40 pm"  >11:40 pm</option>
							<option value="11:45 pm"  >11:45 pm</option>
							<option value="11:50 pm"  >11:50 pm</option>
							<option value="11:55 pm"  >11:55 pm</option>
							<option value="11:59 pm"  >11:59 pm</option>
						</select>
						<span class="error"></span>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="schedulecalllate">End Time</label>
					<div class="controls">
						<select id="schedulecalllate" name="broadcast_schedulecalllate" data-ajax="true">
							<option value="12:00 am"  >12:00 am</option>
							<option value="12:05 am"  >12:05 am</option>
							<option value="12:10 am"  >12:10 am</option>
							<option value="12:15 am"  >12:15 am</option>
							<option value="12:20 am"  >12:20 am</option>
							<option value="12:25 am"  >12:25 am</option>
							<option value="12:30 am"  >12:30 am</option>
							<option value="12:35 am"  >12:35 am</option>
							<option value="12:40 am"  >12:40 am</option>
							<option value="12:45 am"  >12:45 am</option>
							<option value="12:50 am"  >12:50 am</option>
							<option value="12:55 am"  >12:55 am</option>
							<option value="1:00 am"  >1:00 am</option>
							<option value="1:05 am"  >1:05 am</option>
							<option value="1:10 am"  >1:10 am</option>
							<option value="1:15 am"  >1:15 am</option>
							<option value="1:20 am"  >1:20 am</option>
							<option value="1:25 am"  >1:25 am</option>
							<option value="1:30 am"  >1:30 am</option>
							<option value="1:35 am"  >1:35 am</option>
							<option value="1:40 am"  >1:40 am</option>
							<option value="1:45 am"  >1:45 am</option>
							<option value="1:50 am"  >1:50 am</option>
							<option value="1:55 am"  >1:55 am</option>
							<option value="2:00 am"  >2:00 am</option>
							<option value="2:05 am"  >2:05 am</option>
							<option value="2:10 am"  >2:10 am</option>
							<option value="2:15 am"  >2:15 am</option>
							<option value="2:20 am"  >2:20 am</option>
							<option value="2:25 am"  >2:25 am</option>
							<option value="2:30 am"  >2:30 am</option>
							<option value="2:35 am"  >2:35 am</option>
							<option value="2:40 am"  >2:40 am</option>
							<option value="2:45 am"  >2:45 am</option>
							<option value="2:50 am"  >2:50 am</option>
							<option value="2:55 am"  >2:55 am</option>
							<option value="3:00 am"  >3:00 am</option>
							<option value="3:05 am"  >3:05 am</option>
							<option value="3:10 am"  >3:10 am</option>
							<option value="3:15 am"  >3:15 am</option>
							<option value="3:20 am"  >3:20 am</option>
							<option value="3:25 am"  >3:25 am</option>
							<option value="3:30 am"  >3:30 am</option>
							<option value="3:35 am"  >3:35 am</option>
							<option value="3:40 am"  >3:40 am</option>
							<option value="3:45 am"  >3:45 am</option>
							<option value="3:50 am"  >3:50 am</option>
							<option value="3:55 am"  >3:55 am</option>
							<option value="4:00 am"  >4:00 am</option>
							<option value="4:05 am"  >4:05 am</option>
							<option value="4:10 am"  >4:10 am</option>
							<option value="4:15 am"  >4:15 am</option>
							<option value="4:20 am"  >4:20 am</option>
							<option value="4:25 am"  >4:25 am</option>
							<option value="4:30 am"  >4:30 am</option>
							<option value="4:35 am"  >4:35 am</option>
							<option value="4:40 am"  >4:40 am</option>
							<option value="4:45 am"  >4:45 am</option>
							<option value="4:50 am"  >4:50 am</option>
							<option value="4:55 am"  >4:55 am</option>
							<option value="5:00 am"  >5:00 am</option>
							<option value="5:05 am"  >5:05 am</option>
							<option value="5:10 am"  >5:10 am</option>
							<option value="5:15 am"  >5:15 am</option>
							<option value="5:20 am"  >5:20 am</option>
							<option value="5:25 am"  >5:25 am</option>
							<option value="5:30 am"  >5:30 am</option>
							<option value="5:35 am"  >5:35 am</option>
							<option value="5:40 am"  >5:40 am</option>
							<option value="5:45 am"  >5:45 am</option>
							<option value="5:50 am"  >5:50 am</option>
							<option value="5:55 am"  >5:55 am</option>
							<option value="6:00 am"  >6:00 am</option>
							<option value="6:05 am"  >6:05 am</option>
							<option value="6:10 am"  >6:10 am</option>
							<option value="6:15 am"  >6:15 am</option>
							<option value="6:20 am"  >6:20 am</option>
							<option value="6:25 am"  >6:25 am</option>
							<option value="6:30 am"  >6:30 am</option>
							<option value="6:35 am"  >6:35 am</option>
							<option value="6:40 am"  >6:40 am</option>
							<option value="6:45 am"  >6:45 am</option>
							<option value="6:50 am"  >6:50 am</option>
							<option value="6:55 am"  >6:55 am</option>
							<option value="7:00 am"  >7:00 am</option>
							<option value="7:05 am"  >7:05 am</option>
							<option value="7:10 am"  >7:10 am</option>
							<option value="7:15 am"  >7:15 am</option>
							<option value="7:20 am"  >7:20 am</option>
							<option value="7:25 am"  >7:25 am</option>
							<option value="7:30 am"  >7:30 am</option>
							<option value="7:35 am"  >7:35 am</option>
							<option value="7:40 am"  >7:40 am</option>
							<option value="7:45 am"  >7:45 am</option>
							<option value="7:50 am"  >7:50 am</option>
							<option value="7:55 am"  >7:55 am</option>
							<option value="8:00 am"  >8:00 am</option>
							<option value="8:05 am"  >8:05 am</option>
							<option value="8:10 am"  >8:10 am</option>
							<option value="8:15 am"  >8:15 am</option>
							<option value="8:20 am"  >8:20 am</option>
							<option value="8:25 am"  >8:25 am</option>
							<option value="8:30 am"  >8:30 am</option>
							<option value="8:35 am"  >8:35 am</option>
							<option value="8:40 am"  >8:40 am</option>
							<option value="8:45 am"  >8:45 am</option>
							<option value="8:50 am"  >8:50 am</option>
							<option value="8:55 am"  >8:55 am</option>
							<option value="9:00 am"  >9:00 am</option>
							<option value="9:05 am"  >9:05 am</option>
							<option value="9:10 am"  >9:10 am</option>
							<option value="9:15 am"  >9:15 am</option>
							<option value="9:20 am"  >9:20 am</option>
							<option value="9:25 am"  >9:25 am</option>
							<option value="9:30 am"  >9:30 am</option>
							<option value="9:35 am"  >9:35 am</option>
							<option value="9:40 am"  >9:40 am</option>
							<option value="9:45 am"  >9:45 am</option>
							<option value="9:50 am"  >9:50 am</option>
							<option value="9:55 am"  >9:55 am</option>
							<option value="10:00 am"  >10:00 am</option>
							<option value="10:05 am"  >10:05 am</option>
							<option value="10:10 am"  >10:10 am</option>
							<option value="10:15 am"  >10:15 am</option>
							<option value="10:20 am"  >10:20 am</option>
							<option value="10:25 am"  >10:25 am</option>
							<option value="10:30 am"  >10:30 am</option>
							<option value="10:35 am"  >10:35 am</option>
							<option value="10:40 am"  >10:40 am</option>
							<option value="10:45 am"  >10:45 am</option>
							<option value="10:50 am"  >10:50 am</option>
							<option value="10:55 am"  >10:55 am</option>
							<option value="11:00 am"  >11:00 am</option>
							<option value="11:05 am"  >11:05 am</option>
							<option value="11:10 am"  >11:10 am</option>
							<option value="11:15 am"  >11:15 am</option>
							<option value="11:20 am"  >11:20 am</option>
							<option value="11:25 am"  >11:25 am</option>
							<option value="11:30 am"  >11:30 am</option>
							<option value="11:35 am"  >11:35 am</option>
							<option value="11:40 am"  >11:40 am</option>
							<option value="11:45 am"  >11:45 am</option>
							<option value="11:50 am"  >11:50 am</option>
							<option value="11:55 am"  >11:55 am</option>
							<option value="12:00 pm"  >12:00 pm</option>
							<option value="12:05 pm"  >12:05 pm</option>
							<option value="12:10 pm"  >12:10 pm</option>
							<option value="12:15 pm"  >12:15 pm</option>
							<option value="12:20 pm"  >12:20 pm</option>
							<option value="12:25 pm"  >12:25 pm</option>
							<option value="12:30 pm"  >12:30 pm</option>
							<option value="12:35 pm"  >12:35 pm</option>
							<option value="12:40 pm"  >12:40 pm</option>
							<option value="12:45 pm"  >12:45 pm</option>
							<option value="12:50 pm"  >12:50 pm</option>
							<option value="12:55 pm"  >12:55 pm</option>
							<option value="1:00 pm"  >1:00 pm</option>
							<option value="1:05 pm"  >1:05 pm</option>
							<option value="1:10 pm"  >1:10 pm</option>
							<option value="1:15 pm"  >1:15 pm</option>
							<option value="1:20 pm"  >1:20 pm</option>
							<option value="1:25 pm"  >1:25 pm</option>
							<option value="1:30 pm"  >1:30 pm</option>
							<option value="1:35 pm"  >1:35 pm</option>
							<option value="1:40 pm"  >1:40 pm</option>
							<option value="1:45 pm"  >1:45 pm</option>
							<option value="1:50 pm"  >1:50 pm</option>
							<option value="1:55 pm"  >1:55 pm</option>
							<option value="2:00 pm"  >2:00 pm</option>
							<option value="2:05 pm"  >2:05 pm</option>
							<option value="2:10 pm"  >2:10 pm</option>
							<option value="2:15 pm"  >2:15 pm</option>
							<option value="2:20 pm"  >2:20 pm</option>
							<option value="2:25 pm"  >2:25 pm</option>
							<option value="2:30 pm"  >2:30 pm</option>
							<option value="2:35 pm"  >2:35 pm</option>
							<option value="2:40 pm"  >2:40 pm</option>
							<option value="2:45 pm"  >2:45 pm</option>
							<option value="2:50 pm"  >2:50 pm</option>
							<option value="2:55 pm"  >2:55 pm</option>
							<option value="3:00 pm"  >3:00 pm</option>
							<option value="3:05 pm"  >3:05 pm</option>
							<option value="3:10 pm"  >3:10 pm</option>
							<option value="3:15 pm"  >3:15 pm</option>
							<option value="3:20 pm"  >3:20 pm</option>
							<option value="3:25 pm"  >3:25 pm</option>
							<option value="3:30 pm"  >3:30 pm</option>
							<option value="3:35 pm"  >3:35 pm</option>
							<option value="3:40 pm"  >3:40 pm</option>
							<option value="3:45 pm"  >3:45 pm</option>
							<option value="3:50 pm"  >3:50 pm</option>
							<option value="3:55 pm"  >3:55 pm</option>
							<option value="4:00 pm"  >4:00 pm</option>
							<option value="4:05 pm"  >4:05 pm</option>
							<option value="4:10 pm"  >4:10 pm</option>
							<option value="4:15 pm"  >4:15 pm</option>
							<option value="4:20 pm"  >4:20 pm</option>
							<option value="4:25 pm"  >4:25 pm</option>
							<option value="4:30 pm"  >4:30 pm</option>
							<option value="4:35 pm"  >4:35 pm</option>
							<option value="4:40 pm"  >4:40 pm</option>
							<option value="4:45 pm"  >4:45 pm</option>
							<option value="4:50 pm"  >4:50 pm</option>
							<option value="4:55 pm"  >4:55 pm</option>
							<option value="5:00 pm"  >5:00 pm</option>
							<option value="5:05 pm"  >5:05 pm</option>
							<option value="5:10 pm"  >5:10 pm</option>
							<option value="5:15 pm"  >5:15 pm</option>
							<option value="5:20 pm"  >5:20 pm</option>
							<option value="5:25 pm"  >5:25 pm</option>
							<option value="5:30 pm"  >5:30 pm</option>
							<option value="5:35 pm"  >5:35 pm</option>
							<option value="5:40 pm"  >5:40 pm</option>
							<option value="5:45 pm"  >5:45 pm</option>
							<option value="5:50 pm"  >5:50 pm</option>
							<option value="5:55 pm"  >5:55 pm</option>
							<option value="6:00 pm"  >6:00 pm</option>
							<option value="6:05 pm"  >6:05 pm</option>
							<option value="6:10 pm"  >6:10 pm</option>
							<option value="6:15 pm"  >6:15 pm</option>
							<option value="6:20 pm"  >6:20 pm</option>
							<option value="6:25 pm"  >6:25 pm</option>
							<option value="6:30 pm"  >6:30 pm</option>
							<option value="6:35 pm"  >6:35 pm</option>
							<option value="6:40 pm"  >6:40 pm</option>
							<option value="6:45 pm"  >6:45 pm</option>
							<option value="6:50 pm"  >6:50 pm</option>
							<option value="6:55 pm"  >6:55 pm</option>
							<option value="7:00 pm"  >7:00 pm</option>
							<option value="7:05 pm"  >7:05 pm</option>
							<option value="7:10 pm"  >7:10 pm</option>
							<option value="7:15 pm"  >7:15 pm</option>
							<option value="7:20 pm"  >7:20 pm</option>
							<option value="7:25 pm"  >7:25 pm</option>
							<option value="7:30 pm"  >7:30 pm</option>
							<option value="7:35 pm"  >7:35 pm</option>
							<option value="7:40 pm"  >7:40 pm</option>
							<option value="7:45 pm"  >7:45 pm</option>
							<option value="7:50 pm"  >7:50 pm</option>
							<option value="7:55 pm"  >7:55 pm</option>
							<option value="8:00 pm"  >8:00 pm</option>
							<option value="8:05 pm"  >8:05 pm</option>
							<option value="8:10 pm"  >8:10 pm</option>
							<option value="8:15 pm"  >8:15 pm</option>
							<option value="8:20 pm"  >8:20 pm</option>
							<option value="8:25 pm"  >8:25 pm</option>
							<option value="8:30 pm"  >8:30 pm</option>
							<option value="8:35 pm"  >8:35 pm</option>
							<option value="8:40 pm"  >8:40 pm</option>
							<option value="8:45 pm"  >8:45 pm</option>
							<option value="8:50 pm"  >8:50 pm</option>
							<option value="8:55 pm"  >8:55 pm</option>
							<option value="9:00 pm"  >9:00 pm</option>
							<option value="9:05 pm"  >9:05 pm</option>
							<option value="9:10 pm"  >9:10 pm</option>
							<option value="9:15 pm"  >9:15 pm</option>
							<option value="9:20 pm"  >9:20 pm</option>
							<option value="9:25 pm"  >9:25 pm</option>
							<option value="9:30 pm"  >9:30 pm</option>
							<option value="9:35 pm"  >9:35 pm</option>
							<option value="9:40 pm"  >9:40 pm</option>
							<option value="9:45 pm"  >9:45 pm</option>
							<option value="9:50 pm"  >9:50 pm</option>
							<option value="9:55 pm"  >9:55 pm</option>
							<option value="10:00 pm"  >10:00 pm</option>
							<option value="10:05 pm"  >10:05 pm</option>
							<option value="10:10 pm"  >10:10 pm</option>
							<option value="10:15 pm"  >10:15 pm</option>
							<option value="10:20 pm"  >10:20 pm</option>
							<option value="10:25 pm"  >10:25 pm</option>
							<option value="10:30 pm"  >10:30 pm</option>
							<option value="10:35 pm"  >10:35 pm</option>
							<option value="10:40 pm"  >10:40 pm</option>
							<option value="10:45 pm"  >10:45 pm</option>
							<option value="10:50 pm"  >10:50 pm</option>
							<option value="10:55 pm"  >10:55 pm</option>
							<option value="11:00 pm"  >11:00 pm</option>
							<option value="11:05 pm"  >11:05 pm</option>
							<option value="11:10 pm"  >11:10 pm</option>
							<option value="11:15 pm"  >11:15 pm</option>
							<option value="11:20 pm"  >11:20 pm</option>
							<option value="11:25 pm"  >11:25 pm</option>
							<option value="11:30 pm"  >11:30 pm</option>
							<option value="11:35 pm"  >11:35 pm</option>
							<option value="11:40 pm"  >11:40 pm</option>
							<option value="11:45 pm"  >11:45 pm</option>
							<option value="11:50 pm"  >11:50 pm</option>
							<option value="11:55 pm"  >11:55 pm</option>
							<option value="11:59 pm"  >11:59 pm</option>
						</select>
						<span class="error"></span>
					</div>
				</fieldset>				
			</div><!-- field_wrapper -->
			</div><!-- schedule_options -->


			
			<div class="msg_confirm">
				<a href="#" class="toggle-more" data-target="#schedule_options">Schedule</a> or 
				<button class="btn_confirm" id="send_new_broadcast">Send Now <span class="icon"></span></button>
			</div>
			
		</div><!-- end window_panel -->