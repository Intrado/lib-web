<!-- ============== Modal windows and mini forms ================ -->

<!-- choose list modal -->
<div id="msgsndr_choose_list" class="modal hide">
	<h3>Add existing list <a href="" class="close" data-dismiss="modal">x</a></h3>
	<ul id="lists_list">
		<!--li><input type="checkbox"/><label>Exampke</label></li-->
	</ul>
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button>
		<button id="choose_list_add_btn" class="btn_confirm">Add Lists</button>
	</div>
</div>

<!-- build list modal -->				
<div id="msgsndr_build_list" class="modal hide">
	<h3>Add Recipients Using Rules <a href="#" class="close" data-dismiss="modal">x</a></h3>
	<p>Use filters to match a group of entries in your Address Book</p>
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button> 
		<button class="btn_confirm" data-dismiss="modal">Add Lists</button>
	</div>
</div>

<!-- load saved message modal -->
<div id="msgsndr_saved_message" class="modal hide">
	<div class="modal-header">
		<a class="close" data-dismiss="modal">×</a>
		<h3>Load a Saved Message</h3>
	</div>
	<div class="modal_content">
		<div class="scroll">
		<!-- <input type="text"/><input class="btn" type="submit" value="Search"/> -->
			<table class="messages head">
				<thead>
					<tr>
					<th>Title</th>
					<th class="created">Created</th>
					<th class="ico"><img src="themes/newui/images/phone.png" alt=""/></th>
					<th class="ico"><img src="themes/newui/images/email.png" alt=""/></th>
					<th class="ico"><img src="themes/newui/images/sms.png" alt=""/></th>
					<th class="ico"><img src="themes/newui/images/social.png" alt=""/></th>
					</tr>
				</thead>
			</table>
	
			<table class="messages">
			<tbody id="messages_list">
				<!--tr>
					<td>Example</td>
					<td>[mm/dd/yyyy]</td>
					<td>[p]</td>
					<td>[e]</td>
					<td>[t]</td>
					<td>[s]</td>
				</tr-->
			</tbody>
			</table>
		</div>
	</div>
	
	<div class="modal-footer">
		<button data-dismiss="modal">Cancel</button> 
		<button id="msgsndr_load_saved_msg" class="btn-primary" disabled="disabled">Load Selected Message</button>
	</div>

</div>