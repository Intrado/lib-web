							<div class="add-recipients">
								<input type="hidden" id="msgsndr_listids" name="msgsndr_listids" />
								<div class="add-btns cf"> 
									<a href="#add-recipients-existing_lists" class="btn disabled" data-toggle="modal">Pick from Existing Lists</a> 
									<span>or</span>
									<div class="btn-group">
										<a href="#add-recipients-rules" class="btn" data-toggle="modal">Build a List Using Rules</a>
										<a href="#" id="listwidget_enrollment" class="btn dropdown-toggle hidden" data-toggle="dropdown"><span class="caret"></span></a>
										<ul class="dropdown-menu">
											<li><a href="#add-recipients-sections" data-toggle="modal">Use Sections</a></li>
											<li><a href="#add-recipients-quickpick" data-toggle="modal">Quick Pick</a></li>
											<li><a href="#add-recipients-upload" data-toggle="modal">Upload List</a></li>
											
										</ul>
									</div>
								</div>

								<div class="guide add-a-list hide">
									
								</div>

								<div class="added-lists">
									<table class="lists table table-bordered">
										<thead>
											<tr>
												<th colspan="2">List Name</th><th>Count</th>
											</tr>
										</thead>
										<tbody></tbody>
									</table>
								</div>

								<!-- Hidden Modals -->

								<div id="modal-save-list" class="modal hide fade">
									<div class="modal-header">
										<a class="close" data-dismiss="modal">×</a>
										<h3>Save List for Reuse</h3>
									</div>
									<div class="modal-body">
										<!-- <form action="" class="form-horizontal"> -->
											<div class="control-group">
												<label class="control-label" for="">List Name</label>
												<div class="controls">
													<input class="input" type="text" value="Grade is 4; Gender is Male" />
												</div>
											</div>
										<!-- </form> -->
									</div>
									<div class="modal-footer">
										<a href="#" class="btn btn-primary">Save List</a>
										<a data-dismiss="modal" href="#" class="btn">Cancel</a>
									</div>
								</div>
								
								<div id="add-recipients-existing_lists" class="modal hide fade">
									<div class="modal-header">
										<a class="close" data-dismiss="modal">×</a>
										<h3>Add Existing Lists</h3>
									</div>
									<div class="modal-body">
										<div class="existing-lists"></div>
									</div>
									<div class="modal-footer">
										<a href="#" class="btn" data-dismiss="modal">Cancel</a>
										<a href="#" class="btn btn-primary disabled">Add  recipients</a>
									</div>
								</div>

								<div id="add-recipients-rules" class="modal modal-xxlarge hide fade filter-rules">
									<div class="modal-header">
										<a class="close" data-dismiss="modal">×</a>
										<h3>Add Recipients Using Rules</h3>
									</div>
									<div class="modal-body">
										<table class="rules">
											<tr id="new-rule" class="new-rule">
												<td></td>
												<td id="new-rule-field"></td>
												<td id="new-rule-criteria"></td>
												<td id="new-rule-value"></td>

												<td><a href="#" class="btn btn-small btn-primary disabled">Save</a> or <a class="cancel" href="#">cancel</a></td>
											</tr>
										</table>
										<p class="add-rule">
											<a class="btn hide" href="#"><i class="icon-plus icon-white"></i>Add a Rule</a>
										</p>
									</div>
									<div class="modal-footer">
										<a href="#" class="btn" data-dismiss="modal">Cancel</a>
										<a href="#" class="btn btn-primary disabled">Add Recipients</a>
									</div>
								</div>

								<div id="add-recipients-sections" class="modal hide fade">
									<div class="modal-header">
										<a class="close" data-dismiss="modal">×</a>
										<h3>Add Recipients Using Sections</h3>
									</div>
									<div class="modal-body">
										<select class="org-select">
										</select>
										<div class="section-inputs">
											
										</div>
									</div>
									<div class="modal-footer">
										<a href="#" class="btn" data-dismiss="modal">Cancel</a>
										<a href="#" class="btn btn-primary disabled">Add Sections</a>
									</div>
								</div>

								<div id="add-recipients-quickpick" class="modal modaliframe hide fade">
									<div class="modal-header">
										<a class="close" data-dismiss="modal">×</a>
										<h3>Quick Add Recipients</h3>
									</div>
									<div class="modal-body">
										<iframe id="quickpickiframe" src=""></iframe>
									</div>
									<div class="modal-footer">
										<a href="#" class="btn" data-dismiss="modal">Cancel</a>
										<a href="#" class="btn btn-primary" data-dismiss="modal">Done</a>
									</div>
								</div>
								
								<div id="add-recipients-upload" class="modal modaliframe hide fade">
									<div class="modal-header">
										<a class="close" data-dismiss="modal">×</a>
										<h3>Upload List</h3>
									</div>
									<div class="modal-body">
										<iframe id="uploadiframe" src=""></iframe>
									</div>
									<div class="modal-footer">
										<a href="#" class="btn" data-dismiss="modal">Cancel</a>
										<a href="#" class="btn btn-primary" data-dismiss="modal">Done</a>
									</div>
								</div>
								
								
								<div id="modal-preview-list" class="modal modaliframe hide fade">
									<div class="modal-body preview">
										<iframe id="previewlistiframe" src=""></iframe>
									</div>
									<div class="modal-footer">
										<a href="#" class="btn btn-primary" data-dismiss="modal">Done</a>
									</div>
								</div>
								
								<!-- /hidden modals -->
								<div id="msgsndr_listids_msg" class="box_validatorerror er" style="display:none"></div>
								
								
					

<!-- from section_one -->
				<fieldset>
					<input class="addme" type="checkbox" id="msgsndr_addme" name="msgsndr_addme"/>
					<label class="addme" for="msgsndr_addme">Add Myself</label>
					<div id="msgsndr_addme_msg" class="box_validatorerror er" style="display:none"></div>
				</fieldset>

				<div id="addme" class="hide">

					<fieldset>
						<label for="msgsndr_addmephone">Phone&nbsp;<img id="msgsndr_addmephone_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
						<div class="controls">
							<input type="text" id="msgsndr_addmephone" name="msgsndr_addmephone" />
							<span class="error"></span>
							<div id="msgsndr_addmephone_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>

					<fieldset>
						<label for="msgsndr_addmeemail">Email&nbsp;<img id="msgsndr_addmeemail_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
						<div class="controls">
							<input type="text" id="msgsndr_addmeemail" name="msgsndr_addmeemail" />
							<span class="error"></span>
							<div id="msgsndr_addmeemail_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>

					<fieldset>
						<label for="msgsndr_addmesms">SMS&nbsp;<img id="msgsndr_addmesms_icon" class="formicon" src="img/pixel.gif" title="" alt=""></label>
						<div class="controls">
							<input type="text" id="msgsndr_addmesms" name="msgsndr_addmesms" />
							<span class="error"></span>
							<div id="msgsndr_addmesms_msg" class="box_validatorerror er" style="display:none"></div>
						</div>
					</fieldset>

				</div><!-- #addme -->
<!-- end from section_one -->

							</div>
