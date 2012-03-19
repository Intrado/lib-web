/*----- Default theme for site, uses css.inc.php as basic layout example can be seen in example.css -----*/

a, a:visited { color: #484848; }
a:hover { color: <?=$theme1?>; }

/*----- Banner, adds in the logo background and moves the client name to sit above the account navigation -----*/

.banner { background: <?=$topbg?>; border-bottom: 6px solid <?=$primary?>;  }
.banner_logo { background: url(img/shwoosh.png) right center no-repeat; padding: 0 91px 0 0; }
.banner_logo a { background: #fff; padding: 8px 5px; }
.banner_links_wrap { padding: 20px 0 0; }
.banner_links li { padding: 7px 5px 0 5px; background: url(img/accountlinksbg_mid.gif) repeat-x 0 0; font-size: .9em; border-right: 1px solid #ccc; height: 19px; }
.banner_links li.bl_last { border-right: none;  } 
.banner_links li.bl_left { border-right: none; padding: 6px 0 0 0; background: url(img/accountlinksbg_left.gif) no-repeat 0 0; width: 12px;  } 
.banner_links li.bl_right { border-right: none;  background: url(img/accountlinksbg_right.gif) no-repeat 0 0; width: 12px;  } 
.banner_custname { top: 0; right: 0; padding: .25em 1.25em; text-align: right; color: <?=$primary?> }


/*----- Navigation, adds images for link hover and active styles as well as bg image for the primary and sub nav bars -----*/

.primary_nav { background: url("themes/classroom/main_nav_tab_bg.gif") repeat scroll 0 0 transparent; padding: 0 1%; border-top: 2px solid <?=$theme2?>; }
.navtabs li { background: url(themes/<?=$theme?>/main_nav_tab.gif); text-align: center; width: 100px; height: 20px; }
.navtabs li.navtab_active { background: url(themes/<?=$theme?>/main_nav_tab_active.gif); }
.navtabs li:hover { background: url(themes/<?=$theme?>/main_nav_tab_over.gif); }
.navtabs a { padding-top: 3px; font-size: 12px; text-align: center; text-decoration: none; font-weight: bold; color:<?=$primary?>; overflow:hidden; display: block; }

.shortcutmenu { margin: 2px; border: 1px outset; width: 100px; text-align: left; font-size: 10px; }
.shortcutmenu img { margin-right: 5px; margin-left: 5px; }

.subnavtabs { height: 22px; background: url(themes/<?=$theme?>/chrome.png); }
.subnavtabs li { margin-left: 10px; }
.subnavtabs a { height: 18px; padding: 4px 10px 0; }
.subnavtabs a:link, .subnavtabs a:active, .subnavtabs a:visited { color: <?=$primary?>; }
.subnavtabs li.navtab_active { background: #fff; font-weight: bold; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; }


/*----- Content sections, section widths set up here for layout design -----*/

.content_wrap { margin-top: 15px; }
.sectitle { width: 100%; }
.secbutton { width: 15%; }
.sectimeline { width: 85%; }

.pagetitle { font-weight: bold; color: <?=$primary?>;  }

.newjob a { background: url(img/newjob.jpg) center top no-repeat; display: block; width: 100%; height: 122px; text-indent: -9999px; }
.emrjob a { background: url(img/newemergency.jpg) center top no-repeat; display: block; width: 100%; height: 45px; text-indent: -9999px; }

.newjob a:hover, .emrjob a:hover { background-position: center bottom; }

.window {	-webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; 
-webkit-box-shadow: 0 2px 6px #999; -moz-box-shadow: 0 2px 6px #999; box-shadow: 0 2px 6px #999; }

.window_title { background: url(themes/<?=$theme?>/win_t.gif) repeat-x 0 -1px; margin: 0; padding: 3px 0 0 20px; color: #444; font-size: 1em;  height: 20px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;  
-webkit-border-radius: 8px 8px 0 0 ; -moz-border-radius: 8px 8px 0 0 ; border-radius: 8px 8px 0 0 ; }

.feedfilter a { display: block; margin: 0 0 0 12px; padding: 5px 4px; }


/*----- Prototip styles for shortcut menu, further styling for this is in the prototip.css.php file in the css folder -----*/
	
.shortcuts a, .shortcuts a:visited { color: #444444; }
.shortcuts a:hover { background: #92bde5; color: #fff; }

/*----- IE7 classes -----*/

.ie7 div.sectimeline { width: 84.8%; }

/*----- Classes that need PIE -----*/

.window, .window_title, .window_body
{ behavior: url(PIE.php); position: relative;}
