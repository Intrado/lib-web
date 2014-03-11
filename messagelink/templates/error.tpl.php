<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= escapeHtml($this->pageTitle) ?></title>
	<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="css/messagelink.css" rel="stylesheet">
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
		<div id="error-container" class="container">
			<div class="row">
				<div class="col-md-8 col-md-offset-2">
					<div class="alert alert-danger">
						<span class="label label-danger"><span class="glyphicon glyphicon-exclamation-sign" style=""></span></span> &nbsp;
						<strong>Oops! &nbsp; There was an error processing your request.</strong>
						<div id="error-message"><strong>ERROR:</strong> &nbsp;<?= $this->errorMessage ?></div>
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
				&copy; Copyright 1999-<?= date('Y') ?> Reliance Communications Inc. &nbsp;|&nbsp; All Rights Reserved.
			</div>
			<div class="col-xs-4">
				<div class="pull-right">
					<a href="../privacy.html">Privacy Policy</a> &nbsp;|&nbsp; <a href="../terms.html">Terms of Use</a>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
