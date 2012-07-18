
			<!-- ============== Message sender section 1, Subject and Recipients ============== -->
				
			<div id="msg_section_1" class="window_panel hide">
			
			<h3 class="flag">Broadcast Info</h3>
			<div class="field_wrapper">
				<fieldset>
					<label for="msgsndr_name">Subject&nbsp;<img id="msgsndr_name_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<input type="text" id="msgsndr_name" name="msgsndr_name" data-ajax="true" class="required" autocomplete="off" maxlength="50"/> 
						<span class="error"></span>
						<div id="msgsndr_name_msg" class="box_validatorerror er" style="display:none"></div>
						<p>e.g. "PTA Meeting Reminder"</p>
					</div>
				</fieldset>
				
				<fieldset class="cf">
					<label for="msgsndr_jobtype">Type&nbsp;<img id="msgsndr_jobtype_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
					<div class="controls">
						<select id="msgsndr_jobtype" name="msgsndr_jobtype"></select>
						<div id="msgsndr_jobtype_msg" class="box_validatorerror er" style="display:none"></div>
					</div>
				</fieldset>
			</div>
			
			<h3 class="flag">Recipient List</h3>
			<div class="field_wrapper">
<? require("message_sender/listbuilder.inc.php"); ?>
			</div>
			
			<div class="msg_confirm">
					<img name="valspinner" class="hidden" src="img/ajax-loader.gif"><button class="btn_confirm" disabled="disabled" data-next="2">Continue <span class="icon"></span></button>
			</div>
			
			</div><!-- end window_panel -->