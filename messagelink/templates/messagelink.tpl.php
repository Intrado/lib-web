<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= escapeHtml($this->pageTitle) ?></title>
	<link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="css/messagelink.css" rel="stylesheet">
	<script src="script/jquery-1.10.1.js"></script>
	<script src="bootstrap/dist/js/bootstrap.min.js"></script>
	<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
</head>
<body>
<div id="wrap">
	<div id=contentWrapper>
		<div id="header">
			<div class="container">
				<div id="brand" class="pull-left"><?= escapeHtml($this->productName) ?></div>
				<div id="brand-sub" class="pull-right"><?= escapeHtml($this->customerdisplayname) ?></div>
			</div>
		</div>
		<div id="messagelink-container" class="container">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<div class="well well-lg">
						<form class="form-horizontal" role="form">
							<div class="summary-heading">
								<span class="glyphicon glyphicon-earphone"></span> &nbsp;Voice Message Delivery
							</div>
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
										<p class="form-control-static"><?= escapeHtml($this->customerdisplayname) ?></p>
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
										<p class="form-control-static"><?= escapeHtml($this->jobname) ?></p>
									</div>
								</div>
							</div>
							<div id="message-file-wrapper" class="form-group">
								<div class="instruction">
									<span class="glyphicon glyphicon-info-sign"></span> &nbsp;Play your voice message below and/or download the audio file.
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">Message:</label>
									<div class="col-sm-10">
										<div id="player" style="display:inline-block;"></div>
										<script type="text/javascript">
											embedPlayer("<?= (isset($_SERVER["HTTPS"])?"https://":"http://") . $_SERVER['HTTP_HOST'] ?>/m/messagelinkaudio.mp3.php?code=<?=escapehtml($this->messageLinkCode)?>","#player",<?= $this->messageInfo->selectedPhoneMessage->nummessageparts ?>);
										</script>
									</div>
								</div>
								<div class="form-group audiofile">
									<label class="col-sm-2 control-label">Audio File:</label>
									<div class="col-sm-10">
										<a id="download" href="../messagelinkaudio.mp3.php?download&code=<?= escapehtml($this->messageLinkCode) ?>" class="btn btn-primary"><span class="glyphicon glyphicon-download"></span> &nbsp;Download</a>
									</div>
								</div>
							</div>
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
