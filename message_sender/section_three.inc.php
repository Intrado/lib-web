
			
			<!-- ============== Message sender section 3, Review and Send ============== -->

			<div id="msg_section_3" class="window_panel">
			
			<h3 class="flag">Review Message</h3>			
			<div class="field_wrapper">
				<fieldset>
				<label>Subject</label>
					<div class="controls">
						<p>Holidays Reminder</p>
					</div>
				</fieldset>
				
				<fieldset>
					<label>Type</label>
					<div class="controls">
						<p>General Annoucement</p>
					</div>
				</fieldset>
				
				<fieldset>
					<label>Recipients</label>
					<div class="controls">
						<p>2000</p>
					</div>
				</fieldset>
				
				<fieldset>
					<label>Message</label>
					<div class="controls">
						<ul class="msg_complete cf">
							<li class="none">
								<a id="msgsndr_ctrl_phone" href="#"><span class="icon"></span> Phone</a>
							</li>
							<li class="none">
								<a id="msgsndr_ctrl_email" href="#"><span class="icon"></span> Email</a>
							</li>
							<li class="none">
								<a id="msgsndr_ctrl_sms" href="#"><span class="icon"></span> SMS</a>
							</li>
							<li class="none">
								<a id="msgsndr_ctrl_social" href="#"><span class="icon"></span> Social</a>
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
					<label for="msgsndr_form_type">Start Date</label>
					<div class="controls">
						<select id="msgsndr_form_type" name="type">
							<option>05/30/2012</option>
						</select>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="form_days">Days to run</label>
					<div class="controls">
						<select id="form_days" name="type">
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
					<label for="msgsndr_form_type">Start Time</label>
					<div class="controls">
						<select id="msgsndr_form_type" name="type">
							<option>8.00am</option>
							<option>10.00am</option>
							<option>12.00am</option>
							<option>14.00am</option>
							<option>16.00am</option>
						</select>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_form_type">End Time</label>
					<div class="controls">
						<select id="msgsndr_form_type" name="type">
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