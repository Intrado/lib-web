<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT");
header("Content-Type: text/css");
header("Cache-Control: private");

// Tips form styles (tips.php) 

$tipsCSS = '<style type="text/css">
	#tips {
		border-bottom: 1px solid #ddd;
	    margin-bottom: 10px;
	    padding-bottom: 10px;
	}
	#tips-table {
		font-size: 14px;
	}
	#tips-table th {
		padding: 10px;
	}
	#tips select, #tips input {
		font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
		font-size: 14px;
		padding: 6px;
		border: 1px solid #ccc;
	}
	#tip-search-instruction {
	    border-bottom: 1px solid #ddd;
	    color: #444444;
	    font-size: 14px;
	    font-weight: normal;
	    line-height: 21px;
	    margin: 0 15px 10px;
	    padding-bottom: 10px;
	}
	#tip-icon {
		background: url("img/chat32.png") no-repeat center center;
		width: 32px;
		height: 32px;
		float: right;
		opacity: 0.17;
		margin-top: -7px;
		margin-right: 15px;
	}
	#tips #tips_date_reldate {
		float: left;
	}
	#tips #tips_date_reldate + span {
		float: left;
		margin-left: 15px;
	}
	#tips #tips_date_xdays {
		width: 80px;
	}
	#tips #tips_date_dateContainer #tips_date_startdate,
	#tips #tips_date_dateContainer #tips_date_enddate {
		width: 130px;
	}
	#tips #tips_date_dateContainer #tips_date_startdate {
		width: 130px;
		margin-right: 15px;
	}
	#tips button.btn[type=submit] {
		margin-left: 160px;
	}

	#tips-table .attachment {
		background: url("img/glyphicons-paperclip.png") no-repeat center center;
	    height: 18px;
	    width: 20px;
	}

	#tips-table a.attachment {
		background: url("img/glyphicons-paperclip.png") no-repeat 5px center rgba(0, 0, 0, 0);
		border: 1px solid transparent;
	    -webkit-border-radius: 4px;
	       -moz-border-radius: 4px;
	            border-radius: 4px;
		text-decoration: none;
		display: inline-block;
		padding: 13px;
		margin: 0;
		width: 18px;
		height: 18px;
	}
	#tips-table a.attachment:hover {
		background: url("img/glyphicons-paperclip.png") no-repeat 5px center rgba(0, 0, 0, 0.04);
		border: 1px solid #ccc;
	}
	#tips-table a.attachment span {
		font-size: 85%;
		color: #999;
	}
	#tips-table.list tr.listHeader div#carat {
		border-color: #444444 rgba(0, 0, 0, 0) rgba(0, 0, 0, 0);
	    border-style: solid;
	    border-width: 4px;
	    float: right;
	    height: 0;
	    margin-right: 5px;
	    margin-top: 8px;
	    width: 0;
	}
	#tip-view-attachment.modal {
		margin-left: -330px;
	    margin-top: -285px;
	    max-height: 500px;
	    width: 660px;
	}
	#tip-view-attachment .modal-body {
		max-height: 400px;
	}
	#tip-view-attachment #tip-attachment-content {
		text-align: center;
	}
	#tip-view-attachment #attachment-image {
		width: auto;
	}
	#tip-view-attachment .modal-header h3 {
		font-size: 20px;
	}
	</style>';

	echo $tipsCSS;

?>