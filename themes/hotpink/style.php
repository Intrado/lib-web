/*----- Hotpink theme site, uses css.inc.php as basic layout example can be seen in example.css -----*/

body { background: #424242; }
a { color: #333; }
a:hover { color: <?=$theme1?>; }

/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #505050; padding: 15px 25px; border-bottom: 1px dashed #bebebe; }
.banner_logo a { background: #fff; display: block; padding: 6px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}
.banner_links_wrap { padding: 15px 0 0; }
.banner_links li { padding: 0 8px; border-right: 1px dashed #bdbdbd; }
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { color: #e6e6e6; }
.banner_links li a:hover { color: #FF1493; text-decoration: none; }
.banner_custname { float: left; display: inline; margin: 10px 0 0 15px; font: 21px Georgia, arial, sans-serif; color: #f2f2f2;  }


/*----- Navigation, adds grey bg colour, adds pink to navtab links and blue to shortcut button -----*/

.primary_nav { background: #505050; padding: 15px 25px; }
.navtabs li { display: inline; float: left; margin-right: 15px; }
.navtabs a { display: block; padding: 8px 15px; font: 16px georgia, arial, sans-serif; color: #FF1493; text-decoration: none;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.navtabs li.navtab_active a,
.navtabs li:hover a { background: #FF1493; color: #fff; }

.navshortcut { background: #51B3FF; float: right; display: inline; color: #fff; font: 16px Georgia, arial, sans-serif;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.shortcutmenu { padding: 8px 10px 8px 15px; cursor: pointer; }
.shortcutmenu img { background: #fff; margin: 0 0 0 3px; padding: 2px; 
-webkit-border-radius: 6px; -moz-border-radius: 6px; border-radius: 6px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}

.subnavtabs { background: #C1E4FF; width: 98%; padding: 10px 1%; }
.subnavtabs li { margin-right: 12px; }
.subnavtabs li a { padding: 7px 13px; font: 15px Georgia, arial, sans-serif; color: #51B3FF;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.subnavtabs li a:hover,
.subnavtabs li a:active,
.subnavtabs li.navtab_active a { background: #51B3FF; color: #fff; text-decoration: none; }


/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { background: #777; margin: 0; padding: 15px 1%; border-bottom: 1px solid #373737; }
.sectitle { width: 40%; }
.secbutton { width: 60%; }
.sectimeline { width: 100%; }
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: Georgia, arial, sans-serif; color: #51B3FF; }

.newjob { float: right; display: inline; margin: 0 0 15px 15px; }
.newjob a, .emrjob a { font: bold 14px Arial, sans-serif; color: #fff; text-transform: uppercase; text-shadow: 0 0 1px #101010; }
.newjob a { background: url(../img/themes/hotpink/newjob_btn.png) left top no-repeat; width: 131px; height: 30px; padding: 12px 0 0 34px; }
.emrjob { float: right; display: inline; margin: 0 0 15px 15px; }
.emrjob a { background: url(../img/themes/hotpink/emrjob_btn.png) left top no-repeat; width: 103px; height: 30px; padding: 12px 0 0 34px; }
.newjob a:hover, .emrjob a:hover { background-position: right bottom; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { border: 1px solid #373737; }
.window_title { background: #505050; margin: 0; padding: 10px 15px; font: 17px Georgia, arial, sans-serif; color: #f1f1f1; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

.feed_item a { color: #333; }
.feedtitle a:hover, .actionlink:hover { color: #FF1493; }


/*----- Buttons -----*/

.btn { background: #e6e6e6; margin: 0 8px 5px 0; padding: 4px 0; border: none; color: #FF1493; font-weight: bold; cursor: pointer; border: 1px solid #ccc; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}
.btn:hover { background: #FF1493; color: #fff; border: 1px solid #555; }


/*----- timeline.inc.php -----*/

#t_legend span, #t_zoom a { color: #484848; }
#t_zoom a:hover { color: #FF1493; }

/*----- table overrides for list styles -----*/

table.list { width: 100%; margin: 0; }

.bottomBorder { border-bottom: 1px solid #ccc; }
.windowRowHeader { color: #484848; width: 115px; }


/*----- Prototip styles for shortcut menu -----*/
	
.shortcuts a, .shortcuts a:visited { color: #FF1493; }
.shortcuts a:hover { background: #FF1493; color: #fff; }

/*----- IE7 classes -----*/

.ie7 div.secbutton { width: 59.75%; }

/*----- Classes that need PIE -----*/

.banner_logo a, .navshortcut, .shortcutmenu img, .navtabs a, .subnavtabs a, .btn, .window, .window_title, .window_body
{ behavior: url(PIE.php); position: relative;}

