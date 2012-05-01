/*----- New IU theme, uses css.inc.php as basic layout example can be seen in example.css -----*/

/* colours 
``````````````````````````````
#3399ff lighter blue
#346799 mid blue
#26477d dark blue
#2a4470 darker blue

``````````````````````````````*/

/*----- basic colours for theme -----*/
body { background: #e5e5e5 url(themes/newui/bodybg.jpg) repeat; font-family: "Helvetica Neue",helvetica,Arial,sans-serif; }
a, a:visited { color: #484848; }
a:hover { color: #26477d; }
.hide { display: none; }


/*----- Banner -----*/

.banner { background: #346799 url(themes/newui/themebg.jpg) repeat; padding: 0 25px; border-top: 4px solid #222; }
.banner_logo { margin: 30px 0 0; }
.banner_logo h1 { display: block; margin: 0; font-size: 30px; color: #f5f5f5; text-shadow: 0 1px 0 #444; }
.banner_logo a { display: none; }

.banner_links_wrap { margin: 50px 0 0 0; }
.banner_links { list-style-type: none; }
.banner_links li { padding: 0 8px; border-right: 1px solid #e6e6e6; }
.banner_links li.bl_last { border: none; padding: 0 0 0 8px; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { color: #e6e6e6; }
.banner_links li a:hover { text-decoration: underline; }
.banner_custname { top: 24px; right: 25px; text-align: right; font-size: 16px; font-weight: bold; color: #f2f2f2; text-shadow: 0 1px 0 #444; }


/*----- Navigation -----*/

.primary_nav { background: #346799 url(themes/newui/themebg.jpg) repeat; width: 100%; padding: 25px 0 0; border-bottom: 1px solid #2a4470; }
  
.navshortcut { display: none; }
	
.navtabs { margin: 0 0 0 25px; }
.navtabs li { margin: 0 2px -1px 0;}
.navtabs li a { display: block; padding: 14px 18px; font-size: 16px; font-weight: bold; color: #fff; text-decoration: none; text-shadow: 0 1px 0 #444; border: 1px solid transparent;
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.navtabs li a:hover { border: 1px solid #2a4470; 
-webkit-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); -moz-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); }
.navtabs li.navtab_active a { background: #f7f7f7; color: #222; text-shadow: 0 1px 0 #fff; border: 1px solid #2a4470; border-bottom: 1px solid #f7f7f7; }

.subnavtabs { background: #f7f7f7; padding: 5px 0; border-bottom: 1px solid #b6b6b6; }
.subnavtabs li { margin: 5px 0 5px 25px; }
.subnavtabs a { display: block; margin: 0; padding: 5px 11px; font-size: 14px; color: #26477d; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.subnavtabs a:hover,
.subnavtabs a:active,
.subnavtabs .navtab_active a { background: #e2e2e2; color: #242424; text-decoration: none; }


/*----- Content sections -----*/

.content_wrap { position: relative; margin: 10px 0 0 0; padding: 15px; }
.sectitle { width: 100%; }
.secbutton { position: absolute; top: -62px; right: 15px; }
.sectimeline { display: none; }

.pagetitle { font-family: Verdana, Arial, sans-serif; margin: 0.5em 0; color: #333; text-shadow: 0 1px 0 #fff; }


/*----- Notification button -----*/

.emrjob { display: none; }

.newjob a { padding: 5px 10px 3px 10px; color: #fff; font-size: 16px; font-weight: bold; text-shadow: 0 1px 0 #333; border: 1px solid #a9320d;
	background-color: #D8481A;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#E84F1F), to(#C03D14)); 
  background-image: -webkit-linear-gradient(top, #E84F1F, #C03D14); 
  background-image:    -moz-linear-gradient(top, #E84F1F, #C03D14); 
  background-image:     -ms-linear-gradient(top, #E84F1F, #C03D14); 
  background-image:      -o-linear-gradient(top, #E84F1F, #C03D14); 
  background-image:         linear-gradient(top, #E84F1F, #C03D14);
	-webkit-box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); 
	   -moz-box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); 
	        box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.newjob a:before { content: url(themes/newui/cross.png); margin: 0 5px 0 0; vertical-align: middle; }
  
.newjob a:hover { background-color: #E84F1F;
	-webkit-box-shadow: 0px 0px 3px 0px rgba(255,255,255,0.7), inset 0px 15px 0px 0px rgba(255,255,255,0.1); 
		 -moz-box-shadow: 0px 0px 3px 0px rgba(255,255,255,0.7), inset 0px 15px 0px 0px rgba(255,255,255,0.1); 
	        box-shadow: 0px 0px 3px 0px rgba(255,255,255,0.7), inset 0px 15px 0px 0px rgba(255,255,255,0.1);  }

.newjob a:active { background-color: #E84F1F;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#C03D14), to(#a9320d)); 
  background-image: -webkit-linear-gradient(top, #C03D14, #a9320d); 
  background-image:    -moz-linear-gradient(top, #C03D14, #a9320d); 
  background-image:     -ms-linear-gradient(top, #C03D14, #a9320d); 
  background-image:      -o-linear-gradient(top, #C03D14, #a9320d); 
  background-image:         linear-gradient(top, #C03D14, #a9320d);
	-webkit-box-shadow: inset 0px 1px 3px 0px rgba(0,0,0,0.5); 
		 -moz-box-shadow: inset 0px 1px 3px 0px rgba(0,0,0,0.5); 
	        box-shadow: inset 0px 1px 3px 0px rgba(0,0,0,0.5);  }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }


/*----- Window content -----*/

.window { border: none; -webkit-box-shadow: 0px 2px 8px 0px #777; -moz-box-shadow: 0px 2px 8px 0px #777; box-shadow: 0px 2px 8px 0px #777;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.window_title_wrap { background: #f5f5f5; border-bottom: 1px solid #C8C8C8;
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
	background-image: -webkit-gradient(linear, left top, left bottom, from(#fafafa), to(#eeeeee)); 
  background-image: -webkit-linear-gradient(top, #fafafa, #eeeeee); 
  background-image:    -moz-linear-gradient(top, #fafafa, #eeeeee); 
  background-image:     -ms-linear-gradient(top, #fafafa, #eeeeee); 
  background-image:      -o-linear-gradient(top, #fafafa, #eeeeee); 
  background-image:         linear-gradient(top, #fafafa, #eeeeee);
-webkit-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); 
   -moz-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); 
        box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.5); }
        
.window_title { margin: 0; padding: 10px 15px; font-size: 23px; font-weight: bold; color: #242424; text-shadow: 0 1px 0 #fff; }

#t_legend span, #t_zoom a { color: #484848; }
#t_zoom a:hover { color: #26477d; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

.feedtitle a, .feed_content a, .feed_item a, .actionlink  { color: #444; }
.feedtitle a:hover, .feed_title:hover, ul.actionlinks li a:hover { color: #26477d; }

#logininfo, #termsinfo { line-height: 13px; }


/*----- Buttons, changes to the basic buttons featured across the site -----*/

.btn { margin: 0 5px 5px 0; padding: 5px 10px; color: #333; font-size: 14px; font-weight: normal; text-shadow: 0 1px 0 0 #fff; border: 1px solid #ccc;
	background-color: #f5f5f5;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#fbfbfb), to(#e4e4e4)); 
  background-image: -webkit-linear-gradient(top, #fbfbfb, #e4e4e4); 
  background-image:    -moz-linear-gradient(top, #fbfbfb, #e4e4e4); 
  background-image:     -ms-linear-gradient(top, #fbfbfb, #e4e4e4); 
  background-image:      -o-linear-gradient(top, #fbfbfb, #e4e4e4); 
  background-image:         linear-gradient(top, #fbfbfb, #e4e4e4);
	-webkit-box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); 
	   -moz-box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); 
	        box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.btn:hover { background-color: #e7e7e7;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#f2f2f2), to(#dedede)); 
  background-image: -webkit-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:    -moz-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:     -ms-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:      -o-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:         linear-gradient(top, #f2f2f2, #dedede); }
	        
.btn:active { background-color: #d9d9d9;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#e9e9e9), to(#dedede)); 
  background-image: -webkit-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:    -moz-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:     -ms-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:      -o-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:         linear-gradient(top, #e9e9e9, #dedede);
	-webkit-box-shadow: inset 0px 2px 3px 0px rgba(0,0,0,0.25); 
		 -moz-box-shadow: inset 0px 2px 3px 0px rgba(0,0,0,0.25); 
	        box-shadow: inset 0px 2px 3px 0px rgba(0,0,0,0.25);  }

.btn_left, .btn_middle, .btn_right { background: none; margin: 0; padding: 0; }

/*----- table overrides for list styles -----*/

table.list { width: 100%; margin: 0; }

.bottomBorder { border-bottom: 1px solid #ccc; }
.windowRowHeader { color: #484848; width: 115px; }

/*------ Message sender ----- */

.messagescreen { width: 768px; }
.messagescreen .show { display: block; }
.messagescreen .hide { display: none; }

.msg_steps { list-style-type: none; margin: 15px; padding: 0; }
.msg_steps li { position: relative; float: left; display: inline; width: 255px; }
.msg_steps li:first-child { width: 226px; }
.msg_steps li a { background: #f1f1f1; display: block; padding: 10px 11px 10px 40px; color: #777; font-size: 14px; line-height: 30px; font-weight: bold; text-transform: uppercase; text-shadow: 0 1px 0 #fdfdfd; 
border-bottom: 1px solid #cbcbcb; border-top: 1px solid #cbcbcb; outline: 0px; }
.msg_steps li span.icon { display: inline-block; background: #cbcbcb; height: 30px; width: 30px; font-size: 16px; text-align: center; border-radius: 50%; }
.msg_steps li:first-child a { padding-left: 11px; border-left: 1px solid #cbcbcb; border-radius: 8px 0 0 8px; }
.msg_steps li:last-child a { border-right: 1px solid #cbcbcb; border-radius: 0 8px 8px 0; }
.msg_steps li:first-child a:after,
.msg_steps li:first-child a:before { display: none; }

.msg_steps li a:after { content: ''; position: absolute; top: 50%; left: 0; display: block; margin-top: -25px; 
border-color: transparent transparent transparent #f1f1f1; border-width: 25px; border-style: solid; }
.msg_steps li a:before { content: ''; position: absolute; top: 50%; left: 0; display: block; margin-top: -26px; 
border-color: transparent transparent transparent #cbcbcb; border-width: 26px; border-style: solid; }

.msg_steps li.active a { background: #4B9523; color: #fff; border-color: #2C5715; text-shadow: 0 1px 1px #222; }
.msg_steps li.active span.icon { background: #fff; color: #4B9523; text-shadow: none; }
.msg_steps li.active + li a:after { content: ''; position: absolute; top: 50%; left: 0; display: block; margin-top: -25px; 
border-color: transparent transparent transparent #4B9523; border-width: 25px; border-style: solid; }
.msg_steps li.active + li a:before { content: ''; position: absolute; top: 50%; left: 0; display: block; margin-top: -26px; 
border-color: transparent transparent transparent #2C5715; border-width: 26px; border-style: solid; }

.msg_steps li.complete a { background: #3A5F27; color: #C9E3BB; border-color: #111111; text-shadow: 0 1px 1px #222; }
.msg_steps li.complete span.icon { background: #4B9523 url(themes/newui/tick.png) 7px 7px no-repeat; color: #4B9523; text-shadow: none; text-indent: -9999px; }
.msg_steps li.complete + li a:after { content: ''; position: absolute; top: 50%; left: 0; display: block; margin-top: -25px; 
border-color: transparent transparent transparent #3A5F27; border-width: 25px; border-style: solid; }
.msg_steps li.complete + li a:before { content: ''; position: absolute; top: 50%; left: 0; display: block; margin-top: -26px; 
border-color: transparent transparent transparent #111111; border-width: 26px; border-style: solid; }
.msg_steps li.complete a:hover span.icon { background: #4B9523 url(themes/newui/pen.png) 7px 7px no-repeat; }

.msg_steps li.active:after { content: ''; position: absolute; bottom: -16px; left: 50%; display: block; margin-left: -12px; 
border-color: transparent transparent #fff transparent; border-width: 12px; border-style: solid; }
.msg_steps li.active:before { content: ''; position: absolute; bottom: -16px; left: 50%; display: block; margin-left: -13px; 
border-color: transparent transparent #c8c8c8 transparent; border-width: 13px; border-style: solid; }

.window_panel { padding: 22px 15px; font-size: 14px; }
.window_panel a { color: #0088CC; }
.window_panel a:hover { color: #005580; text-decoration: underline; }
.window_panel .icon { display: inline-block; height: 14px; width: 14px; }
.window_panel .btn { display: inline-block; padding: 5px 10px; margin: 0; color: #333; }
.window_panel .btn:hover { color: #222; text-decoration: none; }
.window_panel .record { float: left; display: inline; line-height: 17px; margin: 0 0 0 5px; color: #fff; border: 1px solid #9f320f; 
	background-color: #CF451A;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#E84F1F), to(#A93611)); 
  background-image: -webkit-linear-gradient(top, #E84F1F, #A93611); 
  background-image:    -moz-linear-gradient(top, #E84F1F, #A93611); 
  background-image:     -ms-linear-gradient(top, #E84F1F, #A93611); 
  background-image:      -o-linear-gradient(top, #E84F1F, #A93611); 
  background-image:         linear-gradient(top, #E84F1F, #A93611); }
.window_panel .record:hover { background: #a93611; color: #fff; }
.window_panel .record span.icon { background: url(themes/newui/record.png) 0 center no-repeat; }
.window_panel .audioleft { border-radius: 5px 0 0 5px; }
.window_panel .audioright { border-radius: 0 5px 5px 0; margin-left: -1px; }

.window_panel .btn_confirm { color: #fff; border: 1px solid #0039ab; 
	background-color: #006DCC;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#0088CC), to(#0044CC)); 
  background-image: -webkit-linear-gradient(top, #0088CC, #0044CC); 
  background-image:    -moz-linear-gradient(top, #0088CC, #0044CC); 
  background-image:     -ms-linear-gradient(top, #0088CC, #0044CC); 
  background-image:      -o-linear-gradient(top, #0088CC, #0044CC); 
  background-image:         linear-gradient(top, #0088CC, #0044CC);}
.window_panel .btn_confirm:hover { background: #0044cc; color: #fff; }
.window_panel .btn_confirm:active { background: #0037a4; color: #f4f4f4; }
.window_panel .btn_confirm span.icon { background: url(themes/newui/arrow.png) right 2px no-repeat; }

.window_panel .btn_save { color: #676003; border: 1px solid #dcd13e; 
	background-color: #fff568;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#fff568), to(#e6db47)); 
  background-image: -webkit-linear-gradient(top, #fff568, #e6db47); 
  background-image:    -moz-linear-gradient(top, #fff568, #e6db47); 
  background-image:     -ms-linear-gradient(top, #fff568, #e6db47); 
  background-image:      -o-linear-gradient(top, #fff568, #e6db47); 
  background-image:         linear-gradient(top, #fff568, #e6db47);}
.window_panel .btn_save:hover { background: #e6db47; color: #5d5702; }
.window_panel .btn_save:active { background: #e6db47; color: #5d5702; border-color: #c5ba2c; }

h3.flag { background: #333; margin: 0 0 15px 0; padding: 4px 8px; color: #fff; font-size: 14px; text-transform: uppercase; }

.add_recipients { margin: 0 0 20px 0; }
.add_btn { margin: 0 0 20px 0; }

table.recipient_lists { width: 100%; margin: 0 0 20px 0; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; }
table.recipient_lists th, table.recipient_lists td { font-size: 14px; text-align: left; border-right: 1px solid #ddd; border-top: 1px solid #ddd; }
table.recipient_lists tr:last-child td { background: #fafafa; font-weight: bold; }
table.recipient_lists tr:hover td { background: #fafafa; }
table.recipient_lists tr td:first-child { width: 55px; }
table.recipient_lists a { display: inline-block; width: 18px; height: 18px; }
table.recipient_lists a.removelist { background: url(themes/newui/removelist.png) top left no-repeat; }
table.recipient_lists a.savelist { background: url(themes/newui/savelist.png) top left no-repeat; }
table.recipient_lists a:hover { background-position: bottom right; }

table.messages { width: 100%; margin: 10px 0; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
table.messages th { background: #eee; }
table.messages th, table.messages td { font-size: 14px; text-align: left; border-top: 1px solid #ddd; cursor: pointer; }
table.messages tr:hover td { background: #e1eaf4; border-top: 1px solid #8cb2e0; }

.msg_content_nav { list-style-type: none; margin: 15px 0; padding: 0; }
.msg_content_nav li { position: relative; float: left; display: inline; }
.msg_content_nav li a { display: block; background: #f5f5f5; width: 147px; margin: 0 10px 0 0; padding: 9px 14px; font-size: 20px; color: #888; border-radius: 5px; border: 1px solid #ccc; }
.msg_content_nav li:last-child a { margin: 0; }
.msg_content_nav li a:hover { background: #ededed; color: #888; text-decoration: none; }
.msg_content_nav li a span { color: #444; font-weight: bold; }

.msg_content_nav li a span.icon { background: url(themes/newui/add.png) 0 center no-repeat; }
.msg_content_nav li.active a { background: #363636; color: #f9f9f9; border-color: #222; 
-webkit-box-shadow: inset 0px 1px 3px 0px #111; 
   -moz-box-shadow: inset 0px 1px 3px 0px #111; 
        box-shadow: inset 0px 1px 3px 0px #111; }
.msg_content_nav li.active a:after { content: ""; position: absolute; bottom: -12px; left: 50%; margin-left: -12px; display: block; width: 0; height: 0; 
border-color: #222222 transparent transparent; border-style: solid; border-width: 12px 12px 0; }
.msg_content_nav li.active a span { color: #f9f9f9; }
.msg_content_nav li.active a span.icon { background: url(themes/newui/pencil.png) 0 center no-repeat; }

.tab_content { margin: 0 0 20px 0; }
.tab_panel { background: #fafafa; padding: 20px 15px 0 15px; border: 1px solid #ccc; border-radius: 8px; }

.msg_confirm { background: #F9F8F6; margin: 0 -15px -22px -15px; padding: 22px 15px; text-align: right; border-radius: 0 0 5px 5px; border-top: 1px solid #DDDDDD; }

/*----- Bootstrap modal styles -----*/

.modal-backdrop { background-color: #000000; bottom: 0; left: 0; position: fixed; right: 0; top: 0; opacity: 0.8; z-index: 1040; }

.modal { position: fixed; left: 50%; top: 25%; width: 560px; margin: 0 0 0 -280px; padding: 0; background-clip: padding-box;  background-color: #FFFFFF; 
border: 1px solid rgba(0, 0, 0, 0.3); border-radius: 6px 6px 6px 6px; box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3); overflow: auto; z-index: 1050; }
.modal h3 { position: relative; background: #fdfdfd; font-size: 21px; margin: 0; padding: 15px; border-bottom: 1px solid #ddd; }
.modal .close { position: absolute; top: 18px; right: 15px; color: #999; font-size: 14px; }
.modal .close:hover { color: #666; text-decoration: none; }
.modal ul { list-style-type: none; margin: 0; padding: 15px; }
.modal ul li { padding: 10px 0; border-bottom: 1px solid #eee; }
.modal ul li:last-child { border: none; }
.modal ul li label { padding: 0 0 0 5px; }
.modal .msg_confirm { margin: 0; padding: 15px; }

.modal_content { padding: 15px; }
.modal_content input[type="text"] { padding: 5px 8px; border-radius: 5px 0 0 5px; border: 1px solid #ccc; }
.modal_content input[type="text"]:focus { border: 1px solid #58acef; outline: 0px; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6); }
.modal_content input.btn { border-radius: 0 5px 5px 0; border-left: none; }


/*----- Prototip styles for shortcut menu, not actually used in this theme -----*/
	
.shortcuts a, .shortcuts a:visited { color: #26477d; }
.shortcuts a:hover { background: #26477d; color: #fff; }


/*----- Classes that need PIE -----*/

.navshortcut, .navtabs a, .newjob a, .emrjob a, .banner_links, .banner_logo a, .subnavtabs a, .btn, .window, .window_title_wrap, .window_body
{ behavior: url(PIE.php); position: relative;}


