/* Purplepie theme */


/* colours 
``````````````````````````````
#7259A4 light purple
#463765 mid purple
#392C53 dark purple

``````````````````````````````*/

/* basics
``````````````````````````````*/

body { background: #7259A4; }
body, table, form, select, input { font-family: 'Helvetica Neue', Helvetica, arial, sans-serif;	/*font-size: 13px;*/ }
a { color: #484848; }
a:hover { color: #f78d1d; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #634a94; padding: 0 20px; border-top: 3px solid rgba(0,0,0,0.4); }
.banner_logo { margin: 20px 0; }
.banner_logo a { display: block; }
.banner_logo img { background: #fff; padding: 10px; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 1px 3px 0px rgba(0,0,0,0.8); -moz-box-shadow: 0px 1px 3px 0px rgba(0,0,0,0.8); box-shadow: 0px 1px 3px 0px rgba(0,0,0,0.8); }

.banner_links { background: #101010; background: rgba(0,0,0,0.4); padding: 8px 0; 
-webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.banner_links li { border-right: 1px dashed rgba(255,255,255,0.3); }								             
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { color: #ccc; margin: 0 10px; font: 12px/15px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.banner_links li a:hover { color: #f9f9f9; text-decoration: none; }
.banner_custname { top: 55px; right: 21px; text-align: right; font: 23px/27px 'Helvetica Neue',Helvetica,arial,sans-serif; sans-serif; color: #f3f3f3; text-shadow: 0 1px 0 #333;}


/*----- Navigation -----*/

.primary_nav { background: #695199; padding: 0 20px; }

.primary_nav li { border-right: 1px solid rgba(0,0,0,0.2); }
.primary_nav li:first-child { border-left: 1px solid rgba(0,0,0,0.2); }
.primary_nav li a { display: block; padding: 12px 17px; color: #d5d5d5; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; text-shadow: 0 1px 0 rgba(0,0,0,0.5); }
.primary_nav a:hover, 
.primary_nav a:active,
.primary_nav .navtab_active a { background: #5f478f; } 
.primary_nav .navtab_active a { color: #fff; }
		
.navshortcut { display: none; } 

.subnavtabs { background: #5f478f; width: auto; list-style-type: none; padding: 15px 20px; z-index: 99; }
.subnavtabs li { margin: 0; font: 12px/15px 'Helvetica Neue',Helvetica,arial,sans-serif; border-right: 1px dashed rgba(255,255,255,0.3); }
.subnavtabs li:last-of-type { border: none; }
.subnavtabs a { padding: 0 18px; color: #222;	}	
.subnavtabs a:hover,
.subnavtabs .navtab_active a { text-decoration: none; color: #f4f4f4; }
																	  

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: 0 0 20px; padding: 15px 20px; }
.sectitle { width: 40%; display: none; }
.secbutton { width: 100%; }
.sectimeline { width: 100%; display: none; }
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.newjob { float: left; display: inline; margin: 0 0 20px 0; }
.emrjob { display: none; }

.newjob a { padding: 12px 30px; font: 21px/21px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #FFFFFF; font-size: 21px; }

.newjob a {
	color: #fef4e9; text-shadow: 0 1px 0 rgba(0, 0, 0, 0.8); border: 1px solid #4f4f4f; background: #f78d1d;
	background: -webkit-gradient(linear, left top, left bottom, from(#faa51a), to(#f47a20));
	background: -moz-linear-gradient(top,  #faa51a,  #f47a20);
	filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#faa51a', endColorstr='#f47a20');
	-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; }
	
.newjob a:hover {
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.8); -webkit-box-shadow: 0 1px 4px rgba(0, 0, 0, 0.8); -moz-box-shadow: 0 1px 4px rgba(0, 0, 0, 0.8); }

.newjob a:active {
	background: -webkit-gradient(linear, left top, left bottom, from(#f88e11), to(#f06015));
	background: -moz-linear-gradient(top,  #f88e11,  #f06015);
	filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#f88e11', endColorstr='#f06015');
	box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.8); -webkit-box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.8); -moz-box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.8); }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; }

.window_title { background: #463765; margin: 0; padding: 8px 15px; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #f6f6f6; 
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.window_body_wrap { background: #fff; padding: 0; border-bottom: 1px solid #392C53; border-right: 1px solid #463765; border-left: 1px solid #463765; 
-webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.window_aside { background: #f4f0fc; float: right; margin: 10px 1%; padding: 10px 1.5% 0 1.5%; border: 1px solid #EDE7F9; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.window_main { float: left; margin: 0; width: 75%; }

#feeditems .content_row { width: auto; margin: 1em; padding: 0 0 1em 0; border-bottom: 1px dashed #f1f1f1; }

#t_zoom a { color: #484848; }
#t_zoom a:hover { color: #D14836; }

#filterby { font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.feedfilter a { background: #fff; display: block; margin: 0 0 8px 0; padding: 7px 8px 5px 12px; border: 1px solid #e9e9e9; }
.feedfilter a:hover { color: #f78d1d; }
.feedfilter img { margin: 0 8px 0 0; }

.feed_item { border-bottom: 1px dashed #ccc; }

.feedtitle { font: 15px/25px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.feedtitle a:hover, .feed_title:hover { color: #F47A20; text-decoration: none; }
.feed_content a, .feed_item a { color: #484848;}

.list a, a.actionlink { color: #484848; }
.list a:hover, a.actionlink:hover { color: #f78d1d; }


/*----- Big buttons styling -----*/

.big_btn { float: right; margin-top: -1.5em;}
.big_btn img { display: block; float: left; margin-left: 1em; }


/*----- Footer styling-----*/

#footer { margin: 0 25px; }
#termsinfo, #logininfo { font: bold 9px/14px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #eaeaea; }
#termsinfo a { color: #fdfdfd; text-decoration: underline; }
#termsinfo a:hover { color: #b09cd9; }


/*----- Window buttons -----*/
   
button { border: none; background-color: transparent; }
.btn { margin: .25em .5em; border: none; background: transparent; font-weight: bold; cursor: pointer; }*/
.btn_wrap { white-space: nowrap; position: relative; } 
.btn_hide { position: absolute; left: -9999px; top: -9999px; }
.btn_left, .btn_right { width: 0; height: 0; padding: 0; }
.btn_middle { margin: 0 5px; }

.btn { cursor: pointer; display: inline-block; background-color: #f1f1f1; padding: 5px; color: #555; font-size: 13px; line-height: 15px;
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
  background-image: -webkit-gradient(linear, left top, left bottom, from(#f1f1f1), to(#e6e6e6)); 
  background-image: -webkit-linear-gradient(top, #f1f1f1, #e6e6e6); 
  background-image:    -moz-linear-gradient(top, #f1f1f1, #e6e6e6); 
  background-image:     -ms-linear-gradient(top, #f1f1f1, #e6e6e6); 
  background-image:      -o-linear-gradient(top, #f1f1f1, #e6e6e6); 
  background-image:         linear-gradient(top, #f1f1f1, #e6e6e6);
-webkit-box-shadow: 0px 0px 1px #444; -moz-box-shadow: 0px 0px 1px #444; box-shadow: 0px 0px 1px #444; }
.btn:hover { background: #e6e6e6; color: #333; text-decoration: none; 
-webkit-box-shadow: inset 0px 0px 3px #444; -moz-box-shadow: inset 0px 0px 3px #444; box-shadow: inset 0px 0px 3px #444; }


/*----- Prototip styles for shortcut menu -----*/
	
.shortcuts a, .shortcuts a:visited { color: #333; }
.shortcuts a:hover { background: #fff; color: #FD5701; }


/*----- ie7 styles -----*/

.ie7 div.banner_links_wrap { width: 100%; }
.ie7 div.waside { margin: 0 1.8% 0 0; }


/*----- Classes that need PIE -----*/

.banner_links li a, .navtabs a, .navshortcut, .newjob a, .emrjob a, .btn
{ behavior: url(PIE.php); position: relative;}
