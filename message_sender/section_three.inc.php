
			
			<!-- ============== Message sender section 3, Review and Send ============== -->

			<div id="msg_section_3" class="window_panel">
			
			<h3 class="flag">Review Message</h3>			
			<div class="field_wrapper">
				<fieldset class="review_subject">
					<label>Subject</label>
					<p>Holidays Reminder</p>
				</fieldset>
				
				<fieldset class="review_type">
					<label>Type</label>
					<p>General Annoucement</p>
				</fieldset>
				
				<fieldset class="review_count">
					<label>Recipients</label>
					<p>2000</p>
				</fieldset>
				
				<fieldset>
					<label>Message</label>
					<div class="controls">
						<ul class="msg_complete cf">
							<li class="none">
								<a id="msgsndr_review_phone" href="#"><span class="icon"></span> Phone</a>
							</li>
							<li class="none">
								<a id="msgsndr_review_email" href="#"><span class="icon"></span> Email</a>
							</li>
							<li class="none">
								<a id="msgsndr_review_sms" href="#"><span class="icon"></span> SMS</a>
							</li>
							<li class="none">
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
					<div class="controls">
					<div class="cf"><input class="addme" type="checkbox" id="skip_phones"/><label class="addme" for="skip_phones">Skip Duplicate Phones</label></div>
					</div>
					
					<div class="controls">
					<div class="cf"><input class="addme" type="checkbox" id="save_later"/><label class="addme" for="save_later">Save for later</label></div>
					</div>
				</fieldset>
				</div><!-- adv_options -->
			</div><!-- field_wrapper -->
			
			
			<div id="schedule_options" class="close">
			<h3 class="flag">Schedule Message</h3>			
			<div class="field_wrapper">
				<fieldset class="cf">
					<label for="msgsndr_form_startdate">Start Date</label>
					<div class="controls">
						<input type="text" name="msgsndr_form_startdate" id="msgsndr_form_startdate" />
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_form_daystorun">Days to run</label>
					<div class="controls">
						<select id="msgsndr_form_daystorun" name="type">
							<option>1</option>
							<option>2</option>
							<option>3</option>
							<option>4</option>
							<option>5</option>
							<option>6</option>
							<option>7</option>
						</select>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_form_starttime">Start Time</label>
					<div class="controls">
						<select id="msgsndr_form_starttime" name="type">
							<option>8.00am</option>
							<option>10.00am</option>
							<option>12.00am</option>
							<option>14.00am</option>
							<option>16.00am</option>
						</select>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_form_endtime">End Time</label>
					<div class="controls">
						<select id="msgsndr_form_endtime" name="type">
							<option>8.00pm</option>
							<option>9.00pm</option>
							<option>10.00pm</option>
							<option>11.00pm</option>
							<option>12.00pm</option>
						</select>
					</div>
				</fieldset>				
			</div><!-- field_wrapper -->
			</div><!-- schedule_options -->


			
			<div class="msg_confirm">
				<a href="#" class="toggle-more" data-target="#schedule_options">Schedule Options</a> or 
				<button class="btn_confirm" id="send_new_broadcast">Send Message <span class="icon"></span></button>
			</div>
			
		</div><!-- end window_panel -->