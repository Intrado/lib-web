<?
function escapeHtml($string) {
	return htmlentities($string, ENT_COMPAT, 'UTF-8') ;
}
?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="css/messagelink.css" rel="stylesheet">
	<script src="script/jquery-1.11.0.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>

	<? if ($this->attachmentInfo): ?>
		<script type="text/javascript" src="js/sdd.js"></script>
		<script type="text/javascript">
			$(function () {
				var sdd = new SDD();
				sdd.initialize();
			});
		</script>
	<? else: ?>
		<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
	<? endif; ?>
	<title><?= $this->pageTitle ?></title>
</head>
<body>
<? include_once("analyticstracking.php") ?>
<div id="wrap">
	<div id=content-wrapper>
		<div id="header">
			<div class="container">
				<div id="brand" class="pull-left"><?= escapeHtml($this->productName) ?></div>
				<div id="brand-sub" class="pull-right"><?= escapeHtml($this->customerdisplayname) ?></div>
			</div>
		</div>
	<? if ($this->attachmentInfo): ?>
		<div id="<?= $this->attachmentInfo->isPasswordProtected ? "password-container" : "download-container" ?>" class="container">
	<? else: ?>
		<div id="messagelink-container" class="container">
	<? endif; ?>
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<div class="well well-lg">
						<form class="form-horizontal" role="form">
						<? if ($this->attachmentInfo): ?>
							<? if ($this->attachmentInfo->isPasswordProtected): ?>
							<div class="summary-heading">
								<span class="glyphicon glyphicon-lock"></span> &nbsp;Secure Document Delivery
							</div>
							<? else: ?>
								<div class="instruction download">
									<div id="download-timer-wrapper">
										<span class="glyphicon glyphicon-download"></span> &nbsp;Your download will begin in <span id="download-count" class="text-danger">5</span> seconds...
									</div>
									<p>Problems with the download?  Please use this direct <a class="directlink">link</a>.</p>
									<div class="alert alert-danger" id="download-error-message-container">
										<span class="label label-danger"><span class="glyphicon glyphicon-exclamation-sign" style=""></span></span> &nbsp;<span id="download-error-message">An error occurred while trying to retrieve your document. Please try again.</span>
									</div>
								</div>
							<? endif; ?>
						<? else: ?>
							<div class="summary-heading">
								<span class="glyphicon glyphicon-earphone"></span> &nbsp;Voice Message Delivery
							</div>
						<? endif; ?>
							<div id="email-details">
								<div class="form-group">
									<label class="col-sm-2 control-label">To:</label>
									<div class="col-sm-10">
										<p class="form-control-static"><?= escapeHtml($this->recipient->firstName) ?></p>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">From:</label>
									<div class="col-sm-10">
										<p class="form-control-static">
									<? if ($this->attachmentInfo): ?>
										<a href="mailto:<?= escapeHtml($this->emailMessage->fromEmail) ?>?subject=Re:<?= escapeHtml($this->emailMessage->subject) ?>" ><?= escapeHtml($this->emailMessage->fromName) ?> <span class="normal">&lt;<?= escapeHtml($this->emailMessage->fromEmail) ?>&gt;</span></a>
									<? else: ?>
										<?= escapeHtml($this->customerdisplayname) ?>
									<? endif; ?>
										</p>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Date:</label>
									<div class="col-sm-10">
										<p class="form-control-static"><?= date('M j, Y g:i a', $this->jobstarttime) ?></p>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Subject:</label>
									<div class="col-sm-10">
										<p class="form-control-static">
										<? if ($this->attachmentInfo): ?>
											<?= escapeHtml($this->emailMessage->subject) ?>
										<? else: ?>
											<?= escapeHtml($this->jobname) ?>
										<? endif; ?>
										</p>
									</div>
								</div>
							<? if ($this->attachmentInfo): ?>
								<? if ($this->attachmentInfo->isPasswordProtected): ?>
									<div id="secure-document-wrapper">
										<div class="form-group">
											<label class="col-sm-2 control-label">Document:</label>
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
								<? else: ?>
										<div id="document-wrapper">
											<div class="form-group">
												<label class="col-sm-2 control-label">Document:</label>
												<div class="col-sm-10">
													<span id="filename"><?= $this->attachmentInfo->filename ?></span>
												</div>
												<input type="hidden" id="message-link-code" name="message-link-code" value="<?= $this->messageLinkCode ?>">
												<input type="hidden" id="attachment-link-code" name="attachment-link-code" value="<?= $this->attachmentLinkCode ?>">
											</div>
										</div>
								<? endif; ?>
							<? else: ?>
								<div id="message-file-wrapper" class="form-group">
									<div class="instruction">
										<span class="glyphicon glyphicon-info-sign"></span> &nbsp;Play your voice message below and/or download the audio file.
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Message:</label>
										<div class="col-sm-10">
											<div id="player" style="display:inline-block;"></div>
											<script type="text/javascript">
												embedPlayer("<?= $_SERVER['SCRIPT_URI'] ?>messagelinkaudio.mp3.php?code=<?= escapehtml($this->messageLinkCode) ?>","#player", "<?= $this->messageInfo->selectedPhoneMessage->nummessageparts ?>");
											</script>
										</div>
									</div>
									<div class="form-group audiofile">
										<label class="col-sm-2 control-label">Audio&nbsp;File:</label>
										<div class="col-sm-10">
											<a id="download" href="messagelinkaudio.mp3.php?download&code=<?= escapehtml($this->messageLinkCode) ?>" class="btn btn-primary"><span class="glyphicon glyphicon-download"></span> &nbsp;Download</a>
										</div>
									</div>
								</div>
							<? endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="push"></div>
</div>
<div id="footer">
	<div class="container">
		<div class="row">
			<div class="col-xs-8">
				&copy; Copyright 1999-<?= date('Y') ?> SchoolMessenger &nbsp;|&nbsp; All Rights Reserved.
			</div>
			<div class="col-xs-4">
				<div class="pull-right">
					<a href="privacy.html">Privacy Policy</a> &nbsp;|&nbsp; <a href="terms.html">Terms of Use</a>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
