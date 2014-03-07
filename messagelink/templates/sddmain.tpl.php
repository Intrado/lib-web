<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $this->pageTitle ?></title>
	<link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="css/messagelink.css" rel="stylesheet">
	<script src="script/jquery-1.10.1.js"></script>
	<script src="bootstrap/dist/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/sdd.js"></script>
	<script type="text/javascript">
		$(function () {
			var sdd = new SDD();
			sdd.initialize();
		});
	</script>
</head>
<body>
	<div id="wrap">
		<div id=content-wrapper>
			<div id="header">
				<div class="container">
					<div id="brand" class="pull-left"><?= escapeHtml($this->productName) ?></div>
					<div id="brand-sub" class="pull-right"><?= escapeHtml($this->customerdisplayname) ?></div>
				</div>
			</div>
			<div id="<?= $this->attachmentInfo->isPasswordProtected ? "password-container" : "download-container" ?>" class="container">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<div class="well well-lg">
							<form class="form-horizontal" role="form">
								<? if ($this->attachmentInfo->isPasswordProtected): ?>
								<div class="summary-heading">
									<span class="glyphicon glyphicon-lock"></span> &nbsp;Secure Document Delivery
								</div>
								<? else: $this->downloadTimerView->render() ?>
								<? endif; ?>
								<div id="email-details">
									<div class="form-group">
										<label class="col-sm-2 control-label">To:</label>
										<div class="col-sm-10">
											<p class="form-control-static"><?= escapeHtml($this->recipient->firstName ." ". $this->recipient->lastName) ?></p>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">From:</label>
										<div class="col-sm-10">
											<p class="form-control-static"><a href="mailto:<?= escapeHtml($this->emailMessage->fromEmail) ?>?subject=Re:<?= escapeHtml($this->emailMessage->subject) ?>" ><?= escapeHtml($this->emailMessage->fromName) ?> <span class="normal">&lt;<?= escapeHtml($this->emailMessage->fromEmail) ?>&gt;</span></a></p>
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
											<p class="form-control-static"><?= escapeHtml($this->emailMessage->subject) ?></p>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Message:</label>
										<div class="col-sm-10">
											<p class="form-control-static"><a id="message-modal-trigger" data-toggle="modal" data-target="#view-message-modal"><span class="glyphicon glyphicon-expand"></span>View message body</a></p>
										</div>
									</div>
								</div>
								<div class="modal" id="view-message-modal" tabindex="-1" role="dialog" aria-labelledby="email-message-body" aria-hidden="true">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
												<h4 class="modal-title" id="email-message-body">Email Message Body</h4>
											</div>
											<div class="modal-body">
												<?= escapeHtml($this->emailMessage->plainBody) ?>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
											</div>
										</div>
									</div>
								</div>
								<? $this->mainContentView->render() ?>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="push"></div>
	</div>
	<? $this->footer->render() ?>
</body>
</html>
