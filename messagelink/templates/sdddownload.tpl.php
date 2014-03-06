<div id="document-wrapper">
	<div class="form-group">
		<label class="col-sm-2 control-label">Document</label>
		<div class="col-sm-10">
			<span id="filename"><?= $this->attachmentInfo->filename ?></span>
		</div>
		<input type="hidden" id="message-link-code" name="message-link-code" value="<?= $this->messageLinkCode ?>">
		<input type="hidden" id="attachment-link-code" name="attachment-link-code" value="<?= $this->attachmentLinkCode ?>">
	</div>
</div>