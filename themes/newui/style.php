/* Padding is not added to width of elements, so you can set div etc to the size you want */
* { -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }


/*----- New IU theme, uses css.inc.php as basic layout example can be seen in example.css -----*/

/*----- colours
#3399ff lighter blue
#346799 mid blue
#26477d dark blue
#2a4470 darker blue
-----*/

/*----- basics -----*/
body { background: #F5F3F0 url(themes/newui/bodybg.png) repeat; font-family: "Helvetica Neue",helvetica,Arial,sans-serif; }
p { color: #333; font-size: 14px; line-height: 22px; margin: 0 0 11px 0; }
a, a:visited { color: #0088CC; }
a:hover { color: #005580; text-decoration: underline; }
.hide { display: none; }
h1, h2, h3, h4, h5, h6 { font-family: "Helvetica Neue",helvetica,Arial,sans-serif; color: #333333; font-weight: bold; margin: 0; text-rendering: optimizelegibility; }
h2 { font-size: 24px; line-height: 44px; text-shadow: 0 1px 0 #fff; }
h3 { line-height: 33px; }
h4 { font-size: 12px; line-height: 22px; color: #235563; text-shadow: 0 1px 0 #fff; }

.content_wrap { position: relative; margin: 0; padding: 25px 0 0; }
.container { position: relative; max-width: 95em; margin: 0 auto; padding: 0 10px; }
.wrapper { position: relative; margin: 0 250px 0 0; }
.main_activity { width: 100%; }
.main_aside { position: absolute; top: 0; right: -250px; width: 225px; }
.window_body_wrap { padding: 20px 10px; }


/*----- Banner -----*/

.banner { background: #346799 url(themes/newui/themebg.jpg) repeat; border-top: 4px solid #222; }
.banner_logo { margin: 30px 0 0; }
.banner_logo h1 { display: block; margin: 0; font-size: 30px; color: #f5f5f5; text-shadow: 0 1px 0 #444; }
.banner_logo a { display: none; }

.banner_links_wrap { margin: 50px 0 0 0; }
.banner_links { list-style-type: none; }
.banner_links li { padding: 0 8px; border-right: 1px solid #005580; }
.banner_links li.bl_last { border: none; padding: 0 0 0 8px; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { font-size: 14px; color: #9fbdf6; text-shadow: 0 1px 0 rgba(0,0,0,0.4); }
.banner_links li a:hover { text-decoration: underline; }
.banner_custname { top: 24px; right: 10px; text-align: right; font-size: 16px; font-weight: bold; color: #f2f2f2; text-shadow: 0 1px 0 #444; }


/*----- Navigation -----*/

.primary_nav { background: #346799 url(themes/newui/themebg.jpg) repeat; width: 100%; padding: 25px 0 0; border-bottom: 1px solid #2a4470; }
  
.navshortcut { display: none; }
	
.navtabs li { margin: 0 2px -1px 0;}
.navtabs li a { display: block; padding: 14px 18px; font-size: 16px; font-weight: bold; color: #fff; text-decoration: none; text-shadow: 0 1px 0 #444; border: 1px solid transparent;
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.navtabs li a:hover { border: 1px solid #2a4470; 
-webkit-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); }
.navtabs li.navtab_active a { background: #f7f7f7; color: #222; text-shadow: 0 1px 0 #fff; border: 1px solid #2a4470; border-bottom: 1px solid #f7f7f7; }

.subnavtabs { background: #f7f7f7; padding: 5px 0; border-bottom: 1px solid #b6b6b6; }
.subnavtabs li { position: relative; margin: 5px 10px; }
.subnavtabs a { display: block; margin: 0; padding: 5px 11px; font-size: 14px; color: #26477d; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.subnavtabs a:hover,
.subnavtabs a:active,
.subnavtabs .navtab_active a { background: #e2e2e2; color: #242424; text-decoration: none; }
.subnavtabs .navtab_active:after { content: ''; position: absolute; bottom: -30px; left: 50%; margin-left: -10px; border-color: #f7f7f7 transparent transparent transparent; border-width: 10px; border-style: solid; }
.subnavtabs .navtab_active:before { content: ''; position: absolute; bottom: -32px; left: 50%; margin-left: -11px; border-color: #B6B6B6 transparent transparent transparent; border-width: 11px; border-style: solid; }


/*----- Content sections -----*/

.sectitle { display: none; }
.secbutton { position: absolute; top: -62px; right: 15px; }
.sectimeline { display: none; }

.pagetitle { font-family: Verdana, Arial, sans-serif; margin: 0.5em 0; color: #333; text-shadow: 0 1px 0 #fff; }

.window { border: none; -webkit-box-shadow: 0px 2px 8px 0px #777; box-shadow: 0px 2px 8px 0px #777;
-webkit-border-radius: 5px; border-radius: 5px; -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.users p { position: relative; background: #F5F3F0 url(themes/newui/bodybg.png) repeat; display: inline-block; margin: 0 0 15px 22px; padding: 0 10px; color: #7F7567; font-size: 20px; line-height: 40px; }
.users select { background: #fff; display: inline; width: 150px; margin: 0 0 0 5px; padding: 4px; font-size: 16px; border: 1px solid #ccc; -webkit-border-radius: 3px; border-radius: 3px; }
.users:before { content: ''; position: absolute; top: 20px; left: 0; background: #bbb; height: 1px; width: 100%; }

/*----- Activity summary -----*/

.window_title_wrap { position: relative; padding: 5px 22px; border: 1px solid #2A6576; -webkit-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0;
-webkit-box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5);
	background-color: #3F90A9;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#48A3BE), to(#317589)); 
  background-image: -webkit-linear-gradient(top, #48A3BE, #317589); 
  background-image:    -moz-linear-gradient(top, #48A3BE, #317589); 
  background-image:     -ms-linear-gradient(top, #48A3BE, #317589); 
  background-image:      -o-linear-gradient(top, #48A3BE, #317589); 
  background-image:         linear-gradient(top, #48A3BE, #317589); }
.window_title_wrap h2 { display: inline-block; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.7); }
.window_title_wrap h3 { display: inline-block; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.7); }

.btngroup { position: absolute; right: 22px; top: 13px; }
.btngroup button { background: #f1f1f1; float: left; display: inline; color: #444; margin: 0; padding: 5px 8px; font-size: 13px; text-shadow: 0 1px 0 #fff; 
border-top: 1px solid #bbb; border-right: 1px solid #bbb; border-bottom: 1px solid #bbb; border-left: none; -webkit-border-radius: 0px; border-radius: 0px; 
-webkit-box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); }
.btngroup button:first-child { border-left: 1px solid #bbb; -webkit-border-radius: 4px 0 0 4px; border-radius: 4px 0 0 4px; }
.btngroup button:last-child { -webkit-border-radius: 0 4px 4px 0; border-radius: 0 4px 4px 0; }
.btngroup button:hover { background: #e7e7e7; }
.btngroup button.active { background: #e4e4e4; -webkit-box-shadow: inset 0px 1px 4px 0 rgba(0,0,0,0.2); box-shadow: inset 0px 1px 4px 0 rgba(0,0,0,0.2); }

.summary .window_body_wrap { background: #DFEFF3; padding: 20px 0; border: 1px solid #93C9D9; -webkit-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; }
.summary .col { float: left; display: inline; width: 23%; margin: 0 0 0 1%; padding: 7px 14px; text-align: center; }
.summary .col p { margin: 0; }
.summary .col li { font-size: 14px; line-height: 22px; }
.summary .col li span { display: inline-block; width: 30px; font-weight: bold; color: #48A3BE; }
.summary .col li img { background: #555; display: inline-block; }
.summary strong { font-weight: bold; font-size: 52px; line-height: 52px; }
.summary .bloc { background: #fff; height: 140px; text-align: left; border: 1px solid #93C9D9; -webkit-border-radius: 5px; border-radius: 5px; }


/*----- Broadcasts -----*/

.broadcasts .window_title_wrap { padding: 5px 22px; border: 1px solid #222; -webkit-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0;
-webkit-box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5);
	background-color: #363636;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#444), to(#222)); 
  background-image: -webkit-linear-gradient(top, #444, #222); 
  background-image:    -moz-linear-gradient(top, #444, #222); 
  background-image:     -ms-linear-gradient(top, #444, #222); 
  background-image:      -o-linear-gradient(top, #444, #222); 
  background-image:         linear-gradient(top, #444, #222); }
.broadcasts .window_title_wrap h2 { display: inline-block; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.7); }
.broadcasts .window_body_wrap { background: #fff; padding: 15px 20px; border: 1px solid #eee; -webkit-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; }
.broadcasts h3 span { font-weight: normal; color: #ccc; }


/*----- Big Broadcast button -----*/

a.bigbtn { display: block; width: 100%; margin: 0 0 25px 0; padding: 14px; color: #fff; font-size: 20px; font-weight: bold; text-align: center; border: 3px solid #AE330D;
-webkit-border-radius: 8px; border-radius: 8px;
-webkit-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.25) inset, 0 1px 2px rgba(0, 0, 0, 0.05); box-shadow: 0 1px 0 rgba(255, 255, 255, 0.25) inset, 0 1px 2px rgba(0, 0, 0, 0.05);
text-shadow: 0 1px 1px rgba(0, 0, 0, 0.75); 
background-color: #DF5023;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#F05E31), to(#C63A0E)); 
  background-image: -webkit-linear-gradient(top, #F05E31, #C63A0E); 
  background-image:    -moz-linear-gradient(top, #F05E31, #C63A0E); 
  background-image:     -ms-linear-gradient(top, #F05E31, #C63A0E); 
  background-image:      -o-linear-gradient(top, #F05E31, #C63A0E); 
  background-image:         linear-gradient(top, #F05E31, #C63A0E); }
a.bigbtn:hover { background-color: #C63A0E; text-decoration: none; 
	background-image: -webkit-gradient(linear, left top, left bottom, from(#e35326), to(#C63A0E)); 
  background-image: -webkit-linear-gradient(top, #e35326, #C63A0E); 
  background-image:    -moz-linear-gradient(top, #e35326, #C63A0E); 
  background-image:     -ms-linear-gradient(top, #e35326, #C63A0E); 
  background-image:      -o-linear-gradient(top, #e35326, #C63A0E); 
  background-image:         linear-gradient(top, #e35326, #C63A0E);}
a.bigbtn span { background: url(themes/newui/icon-big-plus.png) no-repeat; display: block; line-height: 30px; padding: 0 0 0 30px; }
  
  
/*----- Broadcast templates -----*/

.templates { margin: 0 0 25px 0; padding: 11px 15px; background-color: #EBE7E1;
background-image: -webkit-gradient(linear, left top, left bottom, from(#F5F3F0), to(rgba(245, 243, 240, 0))); 
  background-image: -webkit-linear-gradient(center top , #F5F3F0, rgba(245, 243, 240, 0)); 
  background-image:    -moz-linear-gradient(center top , #F5F3F0, rgba(245, 243, 240, 0)); 
  background-image:     -ms-linear-gradient(center top , #F5F3F0, rgba(245, 243, 240, 0)); 
  background-image:      -o-linear-gradient(center top , #F5F3F0, rgba(245, 243, 240, 0)); 
  background-image:         linear-gradient(center top , #F5F3F0, rgba(245, 243, 240, 0));
-webkit-border-radius: 5px; border-radius: 5px;
-webkit-box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25) inset; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25) inset; }
.templates h3 { font-size: 14px; text-transform: uppercase; text-shadow: 0 1px 1px rgba(255, 255, 255, 0.85); }
.templates li { font-size: 14px; line-height: 22px; padding: 4px 0; border-top: 1px solid #D6CEC2; }


/*----- Help section -----*/

.help { background-color: #F5F3F0; padding: 11px 15px; border: 1px solid #D6CEC2; -webkit-border-radius: 5px; border-radius: 5px; }
.help h3 { font-size: 16px; }


/*------ Message sender ----- */

.newbroadcast .window_title_wrap { padding: 0 22px 15px 22px; border: 1px solid #2A6576; -webkit-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0;
-webkit-box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); 
	background-color: #3F90A9;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#48A3BE), to(#317589)); 
  background-image: -webkit-linear-gradient(top, #48A3BE, #317589); 
  background-image:    -moz-linear-gradient(top, #48A3BE, #317589); 
  background-image:     -ms-linear-gradient(top, #48A3BE, #317589); 
  background-image:      -o-linear-gradient(top, #48A3BE, #317589); 
  background-image:         linear-gradient(top, #48A3BE, #317589); }
.newbroadcast h2 { padding: 10px 0; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.7); }
.newbroadcast section { background: #fff; border-bottom: 1px solid #eee; -webkit-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; }

.show { display: block; }
.hide { display: none; }

.msg_steps { list-style-type: none; padding: 0; }
.msg_steps li { position: relative; float: left; display: inline; width: 34%; cursor: pointer; }
.msg_steps li:first-child { width: 32%; }
.msg_steps li a { background: #f1f1f1; display: block; padding: 10px 11px 10px 40px; color: #777; font-size: 14px; line-height: 30px; font-weight: bold; text-transform: uppercase; text-shadow: 0 1px 0 #fdfdfd; 
border-bottom: 1px solid #2A6576; border-top: 1px solid #2A6576; outline: 0px; text-decoration: none; -webkit-box-shadow: 0 1px 0 0 rgba(255,255,255,0.4); box-shadow: 0 1px 0 0 rgba(255,255,255,0.4); }
.msg_steps li span.icon { display: inline-block; background: #cbcbcb; height: 30px; width: 30px; font-size: 16px; text-align: center; border-radius: 50%; }
.msg_steps li:first-child a { padding-left: 11px; border-left: 1px solid #cbcbcb; -webkit-border-radius: 8px 0 0 8px; border-radius: 8px 0 0 8px; }
.msg_steps li:last-child a { border-right: 1px solid #2A6576; border-radius: 0 8px 8px 0; }
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
border-color: transparent transparent #FFFFFF transparent; border-width: 12px; border-style: solid; }
.msg_steps li.active:before { content: ''; position: absolute; bottom: -16px; left: 50%; display: block; margin-left: -13px; 
border-color: transparent transparent #2A6576 transparent; border-width: 13px; border-style: solid; }

.window_panel { padding: 0 15px; font-size: 14px; }
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
.window_panel .record span.icon, .call-progress span.icon { background: url(themes/newui/record.png) 0 center no-repeat; }
.window_panel .audioleft { border-radius: 5px 0 0 5px; margin: 0; }
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

h3.flag { padding: 5px 22px; font-size: 18px; color: #fff; border: 1px solid #222; text-shadow: 0 1px 1px rgba(0, 0, 0, 0.75); -webkit-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0;
-webkit-box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5); box-shadow: inset 0px 1px 1px 0 rgba(255,255,255,0.5);
	background-color: #363636;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#444), to(#222)); 
  background-image: -webkit-linear-gradient(top, #444, #222); 
  background-image:    -moz-linear-gradient(top, #444, #222); 
  background-image:     -ms-linear-gradient(top, #444, #222); 
  background-image:      -o-linear-gradient(top, #444, #222); 
  background-image:         linear-gradient(top, #444, #222); }
  
.window_panel form { padding: 20px 0; border: 1px solid #ccc; -webkit-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; }

.field_wrapper { margin: 0 0 20px 0; padding: 20px 0; border: 1px solid #ccc; -webkit-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; }
.add_recipients { margin: 0 0 20px 0; padding: 20px; border: 1px solid #ccc; -webkit-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; }
.add_recipients form { border: none; }
.tab_panel { background: #fafafa; padding: 5px 0 0; border: 1px solid #ccc; -webkit-border-radius: 8px; border-radius: 8px; }
.social_tab { border-bottom: 1px solid #ddd; }
.social_tab:last-child { border: none; }
.add_btn { margin: 0 0 20px 0; }

.review_subject label, .review_type label, .review_count, .review_message label {
  font-weight: bold;
}
.review_count {
  color: rgb(232, 79, 31);
}


#msg_section_3 hr {
  margin: 1em auto;
  width: 95%;
}


/*----- Information tables -----*/

table.info { background: #fff; width: 100%; margin: 0 0 20px 0; font-size: 14px; line-height: 36px; }
table.info thead { border-left: 1px solid #ccc; border-right: 1px solid #ccc; }
table.info th { background: #eee; padding: 0 8px; font-weight: bold; text-align: left; border-bottom: 1px solid #ccc; border-top: 1px solid #ccc; }
table.info td { padding: 0 8px; border-bottom: 1px solid #ccc; }
table.info td:first-child { border-left: 1px solid #ccc; }
table.info td:last-child { border-right: 1px solid #ccc; }
table.info tr:hover td { background: #f5fafb; color: #0064cd; cursor: pointer; }
table.info a.removelist { background: url(themes/newui/removelist.png) left 0 no-repeat; display: inline-block; width: 18px; height: 18px; vertical-align: middle; }
table.info a.savelist { background: url(themes/newui/savelist.png) left 0 no-repeat; display: inline-block; width: 18px; height: 18px; vertical-align: middle; }
table.info a:hover { background-position: right -22px; }
table.info tfoot { border-top: 1px solid #000; }

table.messages { width: 100%; margin: 0; font-size: 14px; line-height: 36px; border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
table.messages.head { margin: 10px 0 0 0;}
table.messages th { background: #eee; padding: 0 8px;}
table.messages th, table.messages td { font-size: 14px; text-align: left; border-top: 1px solid #ddd; cursor: pointer; }
table.messages td { padding: 0 8px; border-bottom: 1px solid #ccc; }
table.messages tr:hover td { background: #e1eaf4; border-top: 1px solid #8cb2e0; }
tr.selected { background: rgb(228, 225, 153); }
table.messages tr.selected:hover td { background: rgb(228, 224, 187);  }
table.messages span.icon { background: url(themes/newui/dktick.png) 0 11px no-repeat; display: block; text-indent: -9999px; }

.scroll {
  max-height: 250px;
  overflow: auto;
}

input[name=msgsndr_msggroup] { 
  display: none;
}


.msg_content_nav { list-style-type: none; margin: 15px 0; padding: 0; }
.msg_content_nav li { position: relative; float: left; display: inline; width: 25%; }
.msg_content_nav li a { display: block; background: #f5f5f5; margin: 0 10px 0 0; padding: 9px 14px; font-size: 20px; color: #888; border-radius: 5px; border: 1px solid #ccc; }
.msg_content_nav li:last-child a { margin: 0; }
.msg_content_nav li a:hover { background: #ededed; color: #888; text-decoration: none; }
.msg_content_nav li a span { color: #444; font-weight: bold; }

.msg_content_nav li a span.icon { background: url(themes/newui/add.png) 0 center no-repeat; width: 16px; }
.msg_content_nav li.active a { background: #363636; color: #f9f9f9; border-color: #222; 
-webkit-box-shadow: inset 0px 1px 3px 0px #111; 
   -moz-box-shadow: inset 0px 1px 3px 0px #111; 
        box-shadow: inset 0px 1px 3px 0px #111; }

.msg_content_nav li.ophone a span.icon   { background-image: url(themes/newui/phone.png) }
.msg_content_nav li.oemail a span.icon   { background-image: url(themes/newui/email.png) }
.msg_content_nav li.osms a span.icon     { background-image: url(themes/newui/sms.png) }
.msg_content_nav li.osocial a span.icon  { background-image: url(themes/newui/social.png) }

.msg_content_nav li.active a:after { content: ""; position: absolute; bottom: -12px; left: 50%; margin-left: -12px; display: block; width: 0; height: 0; 
border-color: #222222 transparent transparent; border-style: solid; border-width: 12px 12px 0; }
.msg_content_nav li.active a span { color: #f9f9f9; }
.msg_content_nav li.active a span.icon, 
.msg_content_nav li.oemail.active a span.icon,
.msg_content_nav li.ophone.active a span.icon,
.msg_content_nav li.osms.active a span.icon,
.msg_content_nav li.osocial.active a span.icon { background: url(themes/newui/pencil.png) 0 center no-repeat; }

.msg_content_nav li.complete a { background: #499122; color: #fbfbfb; text-shadow: 0 1px 1px #222; border-color: #3a7a17; 
-webkit-box-shadow: inset 0px 1px 1px 0px rgba(255,255,255,0.5); 
   -moz-box-shadow: inset 0px 1px 1px 0px rgba(255,255,255,0.5); 
        box-shadow: inset 0px 1px 1px 0px rgba(255,255,255,0.5); }

.msg_content_nav li.complete.active a {
  border-bottom-color: #499122;
}
.msg_content_nav li.complete.active a:after { 
  content: ""; position: absolute; bottom: -12px; left: 50%; margin-left: -12px; display: block; width: 0; height: 0; 
border-color: #499122 transparent transparent; border-style: solid; border-width: 12px 12px 0;
}
.msg_content_nav li.complete a span { color: #fbfbfb; }
.msg_content_nav li.complete a span.icon { background-image: url(themes/newui/tick.png) }


.tab_content { margin: 0 0 20px 0; }
.tab_panel form { border-radius: 8px; }

.msg_complete { list-style-type: none; margin: 5px 0; padding: 0; }
.msg_complete li { position: relative; float: left; display: inline; width: 24%; opacity: 1; }
.msg_complete li:hover { opacity: 0.8; }
.msg_complete li a { display: block; background: #f5f5f5; margin: 0 10px 0 0; padding: 9px 14px; font-size: 20px; font-weight: bold; color: #444; border-radius: 5px; border: 1px solid #ccc; }
.msg_complete li a:hover { color: #444; text-decoration: none; }
.msg_complete li.complete a { background: #499122; color: #fbfbfb; text-shadow: 0 1px 1px #222; border: 1px solid #3A7A17; 
-webkit-box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.5); box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.5); }

.msg_complete li a span.icon { display: none; }
.msg_complete li.complete a span.icon { background: url(themes/newui/tick.png) 0 center no-repeat; width: 16px; display: inline-block; }

.msg_confirm { background: #F9F8F6; margin: 0 -25px -20px -25px; padding: 22px 21px; text-align: right; border-radius: 0 0 5px 5px; border-top: 1px solid #DDDDDD; }


/*----- List page styles -----*/

.feed_btn_wrap { position: absolute; top: -63px; right: 0; margin: 0; padding: 0; border: none; }
.feed_btn_wrap .btn { float: left; display: inline; margin: 0; -webkit-border-radius: 0; border-radius: 0; border-top: 1px solid #bbb; border-right: 1px solid #bbb; border-bottom: 1px solid #bbb; border-left: none; }
.feed_btn_wrap .btn:first-child { border-left: 1px solid #bbb; -webkit-border-radius: 4px 0 0 4px; border-radius: 4px 0 0 4px; }
.feed_btn_wrap .btn:last-child { -webkit-border-radius: 0 4px 4px 0; border-radius: 0 4px 4px 0; }
.feed_btn_wrap .btn:hover { background: #e7e7e7; }
.feed_btn_wrap .btn.active { background: #e4e4e4; -webkit-box-shadow: inset 0px 1px 4px 0 rgba(0,0,0,0.2); box-shadow: inset 0px 1px 4px 0 rgba(0,0,0,0.2); }

/*----- table overrides for list styles -----*/

table.list { background: #fff; width: 100%; margin: 0 0 20px 0; font-size: 14px; line-height: 36px; }
table.list th { background: #eee; padding: 0 8px; font-weight: bold; text-align: left; border-bottom: 1px solid #ccc; border-top: 1px solid #ccc; }
table.list td { padding: 0 8px; border-bottom: 1px solid #ccc; }
table.list ul li { padding: 0; }

.bottomBorder { border-bottom: 1px solid #ccc; }
.windowRowHeader { color: #484848; width: 115px; }


/*----- Buttons, changes to the basic buttons featured across the site -----*/

/* reset the buttons for newui theme so background images aren't loaded */
.btn:hover .btn_middle { background-image: none; background: transparent;}

button, .btn { margin: 0 5px 0 0; padding: 5px 10px; color: #333; font-size: 14px; line-height: 19px; font-weight: normal; text-shadow: 0 1px 0 0 #fff; border: 1px solid #ccc;
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

button:hover, .btn:hover { background-color: #e7e7e7;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#f2f2f2), to(#dedede)); 
  background-image: -webkit-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:    -moz-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:     -ms-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:      -o-linear-gradient(top, #f2f2f2, #dedede); 
  background-image:         linear-gradient(top, #f2f2f2, #dedede); }
	        
button:active, button.active, .btn:active, .btn.active { background-color: #d9d9d9;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#e9e9e9), to(#dedede)); 
  background-image: -webkit-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:    -moz-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:     -ms-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:      -o-linear-gradient(top, #e9e9e9, #dedede); 
  background-image:         linear-gradient(top, #e9e9e9, #dedede);
	-webkit-box-shadow: inset 0px 2px 3px 0px rgba(0,0,0,0.25); 
		 -moz-box-shadow: inset 0px 2px 3px 0px rgba(0,0,0,0.25); 
	        box-shadow: inset 0px 2px 3px 0px rgba(0,0,0,0.25);  }

.window_panel button[disabled], .window_panel button[disabled]:hover {
  background-color: rgb(100,100,100);
  background-image: -webkit-gradient(linear, left top, left bottom, from(#999), to(#AAA)); 
  background-image: -webkit-linear-gradient(top, #999, #AAA); 
  background-image:    -moz-linear-gradient(top, #999, #AAA); 
  background-image:     -ms-linear-gradient(top, #999, #AAA); 
  background-image:      -o-linear-gradient(top, #999, #AAA); 
  background-image:         linear-gradient(top, #999, #AAA);
  border: 1px solid #eee;
  color: rgb(240,240,240);
  cursor: default;
}

.window_panel button .play { background: url(themes/newui/play.png) 0 center no-repeat; }
.window_panel a.toggle-more { background: url(themes/newui/bluearo.png) 2px 4px no-repeat; padding: 0 0 0 15px; }

.btn_left, .btn_right { display: none; }
.btn_left, .btn_middle, .btn_right { background: none; height: 20px; margin: 0; padding: 0 5px; font-size: 13px; line-height: 20px; }


.call-progress, .call-progress:hover {
  background: none;
  -webkit-box-shadow: none;
  -moz-box-shadow: none;
  box-shadow: none;
  cursor: default;
  -webkit-animation: progress-bar-stripes 2s linear infinite;
  -moz-animation: progress-bar-stripes 2s linear infinite;
  animation: progress-bar-stripes 2s linear infinite;
  border: 1px solid #40811e;
  background-color: #55aa28;
  background-image: -webkit-gradient(linear, 0 100%, 100% 0, color-stop(0.25, rgba(255, 255, 255, 0.15)), color-stop(0.25, transparent), color-stop(0.5, transparent), color-stop(0.5, rgba(255, 255, 255, 0.15)), color-stop(0.75, rgba(255, 255, 255, 0.15)), color-stop(0.75, transparent), to(transparent));
  background-image: -webkit-linear-gradient(-45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
  background-image: -moz-linear-gradient(-45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
  background-image: -ms-linear-gradient(-45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
  background-image: -o-linear-gradient(-45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
  background-image: linear-gradient(-45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
  -webkit-background-size: 40px 40px;
  -moz-background-size: 40px 40px;
  -o-background-size: 40px 40px;
  background-size: 40px 40px;
  width: 223px;
  color: #fff;
  padding: 5px 10px; 
  line-height: 19px;
  text-align: center;
}

.call-progress:hover {
  cursor: pointer;
}
/* !!! TODO: Change this to an animated gif for cross browser support !!! */
@-webkit-keyframes progress-bar-stripes {
  from {
    background-position: 0 0;
  }
  to {
    background-position: 40px 0;
  }
}
@-moz-keyframes progress-bar-stripes {
  from {
    background-position: 0 0;
  }
  to {
    background-position: 40px 0;
  }
}
@keyframes progress-bar-stripes {
  from {
    background-position: 0 0;
  }
  to {
    background-position: 40px 0;
  }
}

/* Easycall styles so it's less broken. TODO: these are not final! */
.easycallmaincontainer {}
.easycallmaincontainer div {
	margin-bottom: 3px;
}
.easycallmaincontainer select {
	float: left;
	margin-right: 5px;
	width: auto !important;
}
.easycallmaincontainer input.blank {
	color: gray;
	font-style: italic;
}
.easycalllanguagetitle {
	float: left;
	margin-right: 5px;
	margin-top: 6px;
	margin-left: 2px;
	font-size: 1.5em;
	font-weight: bold;
	font-style: italic;
}
.easycallerrorcontainer {
	padding: 2px;
	background: pink;
}
.easycallerrorcontainer span.easycallerrortext {
	margin-right: 5px;
	margin-left: 2px;
	font-size: 1.2em;
	font-weight: bold;
}
.easycallerrorcontainer span.icon {
	background: url(img/icons/error.png) 0 center no-repeat;
}

/* list picker styles, taken from mockup */
.add-recipients {
	margin-left: 20px;
	margin-right: 20px;
}
.add-recipients div.add-btns {
	margin-bottom: 22px;
}
.btn-group:after {
	clear: both;
}
.btn-group:before, .btn-group:after {
	content: "";
	display: table;
}
.btn-group:before, .btn-group:after {
	content: "";
	display: table;
}
.add-recipients .btn {
	vertical-align: middle;
}
.add-recipients .add-btns .btn-group {
	vertical-align: middle;
	display: inline-block;
	margin-left: 3px;
}
.btn-group {
	position: relative;
}
.btn-group .dropdown-toggle {
	box-shadow: 1px 0 0 rgba(255, 255, 255, 0.125) inset, 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 1px 2px rgba(0, 0, 0, 0.05);
	padding-left: 8px;
	padding-right: 8px;
}
.btn-group .btn:first-child {
	border-bottom-right-radius: 0px;
	border-top-right-radius: 0px;
}
.btn-group .btn:last-child, .btn-group .dropdown-toggle {
	border-bottom-left-radius: 0px;
	border-top-left-radius: 0px;
}
.btn-group .btn {
	float: left;
	margin-left: -1px;
	position: relative;
}
.btn-group.open .dropdown-toggle {
	background-image: none;
	box-shadow: 0 1px 6px rgba(0, 0, 0, 0.15) inset, 0 1px 2px rgba(0, 0, 0, 0.05);
}
.btn-group .dropdown-toggle:active, .btn-group.open .dropdown-toggle {
	outline: 0 none;
}.btn-group.open .dropdown-menu {
	border-radius: 5px 5px 5px 5px;
	display: block;
	margin-top: 1px;
}
.dropdown-menu {
	background-clip: padding-box;
	background-color: #FFFFFF;
	border-color: rgba(0, 0, 0, 0.2);
	border-radius: 0 0 5px 5px;
	border-style: solid;
	border-width: 1px;
	box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
	display: none;
	float: left;
	left: 0;
	list-style: none outside none;
	margin: 0;
	min-width: 160px;
	padding: 4px 0;
	position: absolute;
	top: 100%;
	z-index: 1000;
}
.dropdown-menu a {
	clear: both;
	color: #555555;
	display: block;
	font-weight: normal;
	line-height: 22px;
	padding: 3px 15px;
	white-space: nowrap;
	cursor: pointer;
}
.add-recipients .add-btns .btn-group .caret {
	margin-top: 9px;
}
.btn .caret {
	margin-left: 0;
	margin-top: 7px;
}
.caret {
	border-left: 4px solid transparent;
	border-right: 4px solid transparent;
	border-top: 4px solid #000000;
	content: "↓";
	display: inline-block;
	height: 0;
	opacity: 0.3;
	text-indent: -99999px;
	vertical-align: top;
	width: 0;
}
.add-recipients table.lists {
	border-collapse: separate;
	border-spacing: 0;
}
.add-recipients table.lists tr.new td {
	-moz-transition: background 3s ease 0s;
	background: none repeat scroll 0 0 #FFFF9C;
	border-top: 1px solid #DDDDDD;
	line-height: 22px;
	text-align: left;
}
.add-recipients table.lists tr.new td.flashed {
	background: none repeat scroll 0 0 #FFFFFF;
}
.add-recipients table.lists tr td:first-child {
	padding-right: 0;
	width: 55px;
}
.add-recipients table.lists tr td {
	vertical-align: top;
}
.table-bordered {
	border: 1px solid #DDDDDD;
	border-collapse: separate;
	border-radius: 4px 4px 4px 4px;
	margin-bottom: 22px;
	width: 100%;
	border-spacing: 0;
	max-width: 100%;
	font-size: 16px;
	line-height: 22px;
}
.table-bordered thead:first-child tr:first-child th:first-child, .table-bordered tbody:first-child tr:first-child td:first-child {
	border-radius: 4px 0 0 0;
}
.table-bordered th {
	font-weight: bold;
	vertical-align: bottom;
	line-height: 22px;
	padding: 8px;
	text-align: left;
}
.table-bordered th + th, .table-bordered td + td, .table-bordered th + td, .table-bordered td + th {
	border-left: 1px solid #DDDDDD;
}

/*----- global/helper classes -----*/

.light { color: #999; }
.lighten { opacity: 0.5;}

/*----- Bootstrap modal styles -----*/

.modal-backdrop { background-color: #000000; bottom: 0; left: 0; position: fixed; right: 0; top: 0; opacity: 0.8; z-index: 1040; }

.modal { position: fixed; left: 50%; top: 25%; width: 700px; margin: 0 0 0 -350px; padding: 0; background-clip: padding-box;  background-color: #FFFFFF; 
border: 1px solid rgba(0, 0, 0, 0.3); -webkit-border-radius: 6px; border-radius: 6px; box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3); overflow: auto; z-index: 1050; }
.modal h3 { position: relative; background: #fdfdfd; font-size: 21px; margin: 0; padding: 15px; border-bottom: 1px solid #ddd; -webkit-border-radius: 6px; border-radius: 6px; }
.modal .close { position: absolute; top: 18px; right: 15px; color: #999; font-size: 14px; }
.modal .close:hover { color: #666; text-decoration: none; }
.modal ul { list-style-type: none; margin: 0; padding: 15px; }
.modal ul li { padding: 10px 0; border-bottom: 1px solid #eee; }
.modal ul li:last-child { border: none; }
.modal ul li label { padding: 0 0 0 5px; }
.modal .msg_confirm { margin: 0; padding: 15px; }

.modal_content { padding: 15px; }
.modal_content input[type="text"] { padding: 5px 8px; font-size: 14px; line-height: 19px; border-radius: 5px 0 0 5px; border: 1px solid #ccc; }
.modal_content input[type="text"]:focus { border: 1px solid #58acef; outline: 0px; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6); }
.modal_content input.btn { border-radius: 0 5px 5px 0; border-left: none; }


/*----- Prototip styles for shortcut menu, not actually used in this theme -----*/
	
.shortcuts a, .shortcuts a:visited { color: #26477d; }
.shortcuts a:hover { background: #26477d; color: #fff; }


/*----- Classes that need PIE -----*/

.navshortcut, .navtabs a, .newjob a, .emrjob a, .banner_links, .banner_logo a, .subnavtabs a, .btn, .window, .window_title_wrap, .window_body
{ behavior: url(PIE.php); position: relative;}


