/*----- Sweetred theme site, uses css.inc.php as basic layout example can be seen in example.css -----*/

/* basics
``````````````````````````````*/

body { background: #202020; }
a, a:visited { color: #444; }
a:hover { color: #DF0B0B; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #0f0f0f; padding: 20px 25px; }
.banner_logo a { background: #fff; display: block; padding: 6px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.banner_logo a { padding: .5em; background-color: #fff; opacity: .9;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
										             
.banner_logo a:hover { opacity: 1; }
										             
.banner_links_wrap { padding: 15px 0 0; }
.banner_links li { padding: 0 8px; border-right: 1px dashed #666; }
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { color: #f8f8f8; }
.banner_links li a:hover { color: #DF0B0B; text-decoration: none; }
.banner_custname { float: left; display: inline; margin: 10px 0 0 15px; font: 21px 'Helvetica Neue',Helvetica,arial,sans-serif; sans-serif; color: #f2f2f2;  }


/*----- Navigation -----*/

.primary_nav { background: #202020; padding: 0 25px 15px 25px; border-top: 3px solid #DF0B0B; }
.primary_nav li { margin-right: 1em; }

.primary_nav li a { background: #333; display: block; padding: 7px 14px; color: #fff; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; 
-webkit-border-radius: 0 0 7px 7px; -moz-border-radius: 0 0 7px 7px; border-radius: 0 0 7px 7px; border-bottom: 1px solid #555;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.primary_nav a:hover, 
.primary_nav a:active, 
.primary_nav .navtab_active a { background: #DF0B0B; border-color: #FE875F; }
		
.navshortcut { background: #dbdbdb; color: #333; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif;
-webkit-border-radius: 0 0 7px 7px; -moz-border-radius: 0 0 7px 7px; border-radius: 0 0 7px 7px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.shortcutmenu { padding: 7px 14px; cursor: pointer; }
.shortcutmenu img { padding: 2px; } 

.subnavtabs { background: #141414; width: auto; list-style-type: none; margin: 0 25px 15px; padding: 12px 0; border-bottom: 1px solid #444; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.subnavtabs li { font: 11px/11px 'Helvetica Neue',Helvetica,arial,sans-serif; border-right: 1px dashed #555555; margin: 0; }
.subnavtabs li:last-of-type { border: none; }
.subnavtabs li a { padding: 0 15px; font: 12px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #fff; }	
		
.subnavtabs a:hover,
.subnavtabs a:focus,
.subnavtabs a:active,
.subnavtabs .navtab_active a { color: #DF0B0B; text-decoration: none; }
			

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: 0; padding: 0 25px 15px 25px; }
.sectitle { width: 40%; }
.secbutton { width: 60%; }
.sectimeline { width: 100%; }
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: #7f7f7f; }

.newjob, .emrjob { float: right; display: inline; margin: 0 0 15px 15px; }
.newjob a, .emrjob a { padding: 12px 18px; border: 1px solid #000; color: #fff; text-transform: uppercase; background: #DF0B0B;
font: bold 14px 'Helvetica Neue',Helvetica,arial,sans-serif; text-shadow: 0 1px 0 #222;
-webkit-border-radius: 7px; -moz-border-radius: 7px; border-radius: 7px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
 -webkit-box-shadow: inset 0px 0px 4px #101010; -moz-box-shadow: inset 0px 0px 4px #101010; box-shadow: inset 0px 0px 4px #101010; }
.newjob a:hover, .emrjob a:hover { background: #960606; color: #f9f9f9; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; }

.window_title_wrap { background: #dbdbdb; -webkit-box-shadow: 0px 1px 2px #666; -moz-box-shadow: 0px 1px 1px #666; box-shadow: 0px 1px 2px #666; 
-webkit-border-radius: 3px 3px 0 0; -moz-border-radius: 3px 3px 0 0; border-radius: 3px 3px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.window_title { margin: 0 15px; padding: 10px 0; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; border-bottom: 1px solid #dbdbdb; }

.window_body_wrap { background: #fff; -webkit-box-shadow: 0px 1px 2px #666; -moz-box-shadow: 0px 1px 1px #666; box-shadow: 0px 1px 2px #666;
-webkit-border-radius: 0 0 4px 4px; -moz-border-radius: 0 0 4px 4px; border-radius: 0 0 4px 4px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

#t_legend span, #t_zoom a { color: #484848; }
#t_zoom a:hover { color: #DF0B0B; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

.feed_item a, .feed_content a, .actionlink { color: #444; }
.feedtitle a:hover, .feed_title:hover, ul.actionlinks li a:hover { color: #DF0B0B; }


/*----- Footer styling-----*/

#footer { margin: 0 25px; }
#termsinfo, #logininfo { font: bold 9px/14px Arial,sans-serif; color: #888; }


/*----- Buttons -----*/

.btn { margin: 0 5px 5px 0; padding: 7px 12px; border: 1px solid #000; color: #fff; text-transform: uppercase;
font: bold 12px 'Helvetica Neue',Helvetica,arial,sans-serif; text-shadow: 0 1px 0 #222; background: #DF0B0B;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: inset 0px 0px 4px #555; -moz-box-shadow: inset 0px 0px 4px #555; box-shadow: inset 0px 0px 4px #555; }

.btn:hover { background: #960606; color: #f9f9f9; }

.btn_middle, .btn_left, .btn_right { width: auto; height: auto; margin: 0; padding: 0; }


/*----- table overrides for list styles -----*/

table.list { width: 100%; margin: 0; }

.bottomBorder { border-bottom: 1px solid #ccc; }
.windowRowHeader { color: #484848; width: 115px; }


/*----- Prototip styles for shortcut menu -----*/
	
.shortcuts a, .shortcuts a:visited { color: #DF0B0B; }
.shortcuts a:hover { background: #DF0B0B; color: #fff; }

/*----- IE7 classes -----*/

.ie7 div.secbutton { width: 59.75%; }

/*----- Classes that need PIE -----*/

.banner_logo a, .navshortcut, .shortcutmenu img, .navtabs a, .subnavtabs, .btn, .window, .window_title, .window_body, .emrjob a, .newjob a
{ behavior: url(PIE.php); position: relative;}

