<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT");
header("Content-Type: text/css");
header("Cache-Control: private");

?>
/* CSS to go with Prototip.Styles */
.prototip {
	font: 11px Arial, Helvetica, sans-serif;
	color: #000;
}

/* the default style */
.prototip .default {
	width: 250px;
	color: #808080;
}
.prototip .default .toolbar {
	background: #f1f1f1;
	font-weight: bold;
}
.prototip .default .title { padding: 5px; }
.prototip .default .content {
	padding: 5px;
	background: #fff;
}

/* basic */
.prototip .basic {
	width: 250px;
	color: #808080;
}
.prototip .basic .toolbar {
	background: #f1f1f1;
	font-weight: bold;
}
.prototip .basic .title { padding: 5px; }
.prototip .basic .content {
	padding: 5px;
	background: #fff;
}

/* basic */
.prototip .hint {
	width: 250px;
	color: #313120;
	background: #fff6aa;
}
.prototip .hint .toolbar {
	background: #fdf1a0;
	font-weight: bold;
}
.prototip .hint .title,
.prototip .hint .content { padding: 5px; }

/* protoblue */
.prototip .protoblue {
	width: 250px;
	color: #fff;
}
.prototip .protoblue .toolbar {
	background: #0d7cd0;
	font-weight: bold;
}
.prototip .protoblue .title { padding: 5px; }
.prototip .protoblue .content {
	background: #1e90ff;
	padding: 5px;
}

/* creamy */
.prototip .creamy {
	width: 250px;
	color: #bb9c61;
}
.prototip .creamy .toolbar {
	background: #f3edc2;
	font-weight: bold;
}
.prototip .creamy .title { padding: 5px; }
.prototip .creamy .content {
	background: #f8f4ca;
	padding: 5px;
}

/* darkgrey */
.prototip .darkgrey {
	width: 250px;
	color: #fff;
}
.prototip .darkgrey .toolbar {
	background: #5f5f5f;
	font-weight: bold;
}
.prototip .darkgrey .title { padding: 5px; }
.prototip .darkgrey .content {
	background: #808080;
	padding: 5px;
}

/* protogrey */
.prototip .protogrey {
	width: 250px;
	color: #fff;
	background: #fff;
}
.prototip .protogrey .toolbar {
	background: #969c92;
	font-weight: bold;
}
.prototip .protogrey .title { padding: 5px; }
.prototip .protogrey .content {
	color: #808080;
	padding: 5px;
}


/*----- fresh -----*/

.prototip .fresh { width: 250px; color: #121212; border-top: 1px solid #fff; }
.prototip .fresh .toolbar { background: #f3edc2; font-weight: bold; }
.prototip .fresh .title { padding: 2px; }
.prototip .fresh .content { background: #606060; }


/* This is how to resize the close button for a style */
.prototip .protogrey .toolbar .close {
	width: 14px;
	height: 14px;
}


/* loader gif */
.prototipLoader {
	position: absolute;
	top: -1000px;
	left: -1000px;
	height: 14px;
	width: 14px;
	border: 1px solid #dddddd;
	overflow: hidden;
}


/* Required for all tooltips, do not modify */
.prototip{position:absolute;overflow:hidden;}.prototip .tooltip,.prototip .toolbar,.prototip .toolbar .title{position:relative;}.prototip .content{clear:both;}.prototip .toolbar .close{position:relative;text-decoration:none;float:right;width:19px;height:15px;display:block;line-height:0;font-size:0;border:0;cursor:pointer;}.prototip .tooltip{clear:both;float:left;}.prototip .borderLeftWrapper,.prototip .borderRightWrapper{position:absolute;top:0;left:0;width:300px;height:20px;}.prototip .borderFrame{height:100%;width:100%;float:left;margin:0;padding:0;position:relative;}.prototip .borderTop,.prototip .borderBottom{overflow:hidden;}.prototip .borderRow{list-style-type:none;float:left;width:100%;position:relative;clear:both;margin:0;padding:0;}.prototip_CornerWrapper{position:absolute;top:0;left:0;width:100%;height:100%;margin:0;padding:0;clear:both;}.prototip_Corner{float:left;position:relative;}.prototip canvas{position:relative;float:left;}.prototip_CornerTr,.prototip_CornerBr{float:right;}.prototip_BetweenCorners{position:absolute;top:0;left:0;width:100%;overflow:hidden;clear:both;}.prototip .borderMiddle{position:relative;float:left;}.prototip .borderCenter{position:relative;float:left;height:100%;}.prototip_StemWrapper{position:relative;width:100%;height:auto;clear:both;}.prototip_StemBox{float:left;position:relative;}.prototip_Stem{width:100%;position:absolute;overflow:hidden;}.iframeShim{position:absolute;border:0;margin:0;padding:0;background:none;overflow:hidden;}.prototip .clearfix:after{content:".";display:block;height:0;clear:both;visibility:hidden;}.prototip .clearfix{display:inline-block;}/* IE Mac Hide \*/ .prototip .clearfix{display:block;}/* IE Mac Hide End */