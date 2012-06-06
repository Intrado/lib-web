
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
						<select id="msgsndr_form_type" name="broadcast_type"></select>
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

				<input type="hidden" id="list_ids" name="broadcast_listids" value="" class="required" />

				<fieldset id="msgsndr_list_choices"class="hidden">
					<!-- hidden inputs for each selected list get appended here -->	
				</fieldset>
							
				<table id="msgsndr_list_info" class="info">
					<thead>
						<tr>
							<th colspan="2">List Name</th>
							<th>Count</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="2">Total</td>
							<td>[total]</td>
						</tr>
					</tfoot>
					<tbody id="msgsndr_list_info_tbody">
						<!--tr>
							<td>
							<a class="removelist" href="" title="Remove List"></a>
							<a class="savelist" href="" title="Save List"></a>
							</td>
							<td>List name goes here</td>
							<td>1000</td>
						</tr-->
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