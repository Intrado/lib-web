<?

?>
.content_wrap { min-width: auto;}

.pagetitle {
	display: none;
}
/************************************************************************************
MOBILE
*************************************************************************************/
@media screen and (max-width: 600px) {
	
	.contactmanagersplash {
		display: none;
	}
	
	.banner_logo {
		/*background:none;*/
	}
	.logo {
		overflow:hidden;	
	}
	
	.banner_logo img {	
		margin:0 -206px 0 0;
		/*width: 400px; height: 300px;*/
	}
	.windowRowHeader.bottomBorder { width: 0px;display:none; }
	
	
	.banner_links_button {
		display: block;
	}
	.banner_links.minhide {
		display: none;
	}
	
	.banner_links li.bl_left {
		display: none;
	}
	.banner_links li.bl_right {
		display: none;
	}
		/* main nav */
	.banner_links {
		clear: both;
		position: absolute;
		top: 40px;
		z-index: 10000;
		padding: 0 5px 5px 0px;
		background: white;
		border: solid 1px #999;
	}
	.banner_links li {
		background: none;
		display: inherit;
		clear: both;
		float: none;
		border-right: none;
		margin: 5px 0 5px 10px;
	}
	#banner_links a, 
	#banner_links ul a {
		font: inherit;
		background: none;
		display: inline;
		padding: 0;
		color: #666;
		border: none;
	}
	#banner_links a:hover, 
	#banner_links ul a:hover {
		background: none;
		color: #000;
	}
	
	/* dropdown */
	#banner_links ul {
		width: auto;
		position: static;
		display: block;
		border: none;
		background: inherit;
	}
	#banner_links ul li {
		margin: 3px 0 3px 15px;
	}
	
}

@media screen and (min-width: 600px) {
	.banner_links_button {
		display: none;
	}
	
	.banner_links.minhide {
		display: block;
	}
}

@media screen and (max-width: 700px) {
	#logininfo { clear:both; float:none; display: block; text-align: left; }
	#termsinfo { clear:both; float:none; display: block; text-align: left;}
}




