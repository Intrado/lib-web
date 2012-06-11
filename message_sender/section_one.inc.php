
			<!-- ============== Message sender section 1, Subject and Recipients ============== -->
				
			<div id="msg_section_1" class="window_panel">
			
			<h3 class="flag">Broadcast Info</h3>
			<div class="field_wrapper">
				<fieldset>
				<label for="msgsndr_form_subject">Subject</label>
					<div class="controls">
						<input type="text" id="msgsndr_form_subject" name="broadcast_subject" data-ajax="true" class="required" autocomplete="off" /> 
						<span class="error"></span>
						<p>e.g. "PTA Meeting Reminder"</p>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_form_type">Type</label>
					<div class="controls">
						<select id="msgsndr_form_type" name="broadcast_type"></select>
					</div>
				</fieldset>
			</div>
			
			<h3 class="flag">Recipient List</h3>
			<div class="field_wrapper">
<? require("message_sender/listbuilder.inc.php"); ?>
			</div>
			
			<div class="msg_confirm">
					<button class="btn_confirm" disabled="disabled" data-next="2">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->