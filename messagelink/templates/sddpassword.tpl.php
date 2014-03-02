<div id="secure-document-wrapper">
	<span>Secure Document:</span><span id="filename"><?= $this->attachmentInfo->filename ?></span></span>
</div>
<div class="instruction">
	<span class="glyphicon glyphicon-info-sign"></span> &nbsp;Please enter the password provided by your student's school or district to download the secure document.
</div>
<div class="form-group">
	<label class="col-sm-2 control-label">Password</label>
	<div class="col-sm-6 input-group">
		<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
		<input type="password" class="form-control" placeholder="Password">
		<span class="input-group-btn">
			<button id="download" type="submit" class="btn btn-primary disabled" disabled="disabled">Download</button>
		</span>
	</div>
</div>
