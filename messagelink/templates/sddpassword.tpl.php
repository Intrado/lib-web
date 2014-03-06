<div id="secure-document-wrapper">
	<div class="form-group">
		<label class="col-sm-2 control-label">Document</label>
		<div class="col-sm-10">
			<span id="filename"><?= $this->attachmentInfo->filename ?></span>
		</div>
	</div>
</div>
<div class="instruction">
	<span class="glyphicon glyphicon-info-sign"></span> &nbsp;Please enter the password provided by your student's school or district to download the secure document.
</div>
<div class="form-group">
	<label class="col-sm-2 control-label">Password</label>
	<div class="col-sm-7">
		<div class="input-group">
			<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
			<input id="password" name="password" type="password" class="form-control" placeholder="Password">
			<span class="input-group-btn">
				<button id="downloadB" type="submit" class="btn btn-primary disabled" disabled="disabled">Download</button>
			</span>
		</div>
		<input type="hidden" id="message-link-code" name="message-link-code" value="<?= $this->messageLinkCode ?>">
		<input type="hidden" id="attachment-link-code" name="attachment-link-code" value="<?= $this->attachmentLinkCode ?>">
		<div id="download-error-message-container" class="alert alert-danger">
			<span class="label label-danger"><span class="glyphicon glyphicon-exclamation-sign" style=""></span></span> &nbsp;<span id="download-error-message"></span>
		</div>
	</div>
</div>
