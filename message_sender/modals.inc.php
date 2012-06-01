<!-- ============== Modal windows and mini forms ================ -->

<!-- choose list modal -->
<div id="msgsndr_choose_list" class="modal hide">
	<h3>Add existing list <a href="" class="close" data-dismiss="modal">x</a></h3>
	<ul id="lists_list">
		<!--li><input type="checkbox"/><label>Exampke</label></li-->
	</ul>
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button>
		<button id="choose_list_add_btn" class="btn_confirm" href="">Add Lists</button>
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
	<h3>Load a Saved Message <a href="" class="close" data-dismiss="modal">x</a></h3>
	<div class="modal_content">
	<input type="text"/><input class="btn" type="submit" value="Search"/>
	<table class="messages">
		<thead>
			<tr>
			<th>Title</th>
			<th>Created</th>
			<th><img src="themes/newui/phone.png" alt=""/></th>
			<th><img src="themes/newui/email.png" alt=""/></th>
			<th><img src="themes/newui/sms.png" alt=""/></th>
			<th><img src="themes/newui/social.png" alt=""/></th>
			</tr>
		</thead>
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
	
	<div class="msg_confirm">
		<button data-dismiss="modal">Cancel</button> 
		<button id="msgsndr_load_saved_msg" class="btn_confirm">Load Selected Message</button>
	</div>

</div>