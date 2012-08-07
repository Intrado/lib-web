/*----- Default theme for site, uses css.inc.php as basic layout example can be seen in example.css -----*/

a { color: <?=$theme1?>; }
a:hover { text-decoration: underline; }

/*----- Banner, adds in the logo background and moves the client name to sit above the account navigation -----*/

.banner { background: <?=$topbg?>; border-bottom: 6px solid <?=$primary?>;  }
.banner_logo { background: url(img/shwoosh.gif) right center no-repeat; height: 50px; padding: 0 91px 0 0; }
.logo { background: #fff; height: 50px; margin: 0; }
.logo td { vertical-align: middle; padding: 0 0 0 10px; }
.banner_links_wrap { padding: 20px 0 0; }
.banner_links li { padding: 7px 5px 0 5px; background: url(img/accountlinksbg_mid.gif) repeat-x 0 0; font-size: .9em; border-right: 1px solid #ccc; height: 19px; }
.banner_links li.bl_last { border-right: none;  } 
.banner_links li.bl_left { border-right: none; padding: 6px 0 0 0; background: url(img/accountlinksbg_left.gif) no-repeat 0 0; width: 12px;  } 
.banner_links li.bl_right { border-right: none;  background: url(img/accountlinksbg_right.gif) no-repeat 0 0; width: 12px;  } 
.banner_custname { top: 0; right: 0; padding: .25em 1.25em; text-align: right; color: <?=$primary?> }

.popup_logo { padding: 10px 1% 0 1%; background: url(img/header_bg.gif) 0 0 repeat-x; }
.popup_logo img { margin: 5px 0 0; }
.popup_logo h1 { display: none; }


/*----- Navigation, adds images for link hover and active styles as well as bg image for the primary and sub nav bars -----*/

.primary_nav { background: url("themes/classroom/main_nav_tab_bg.gif") repeat scroll 0 0 transparent; padding: 0 1%; border-top: 2px solid <?=$theme2?>; }
.navtabs li { background: url(themes/<?=$theme?>/main_nav_tab.gif); text-align: center; width: 100px; height: 20px; }
.navtabs li.navtab_active { background: url(themes/<?=$theme?>/main_nav_tab_active.gif); }
.navtabs li:hover { background: url(themes/<?=$theme?>/main_nav_tab_over.gif); }
.navtabs a { padding-top: 3px; font-size: 12px; text-align: center; text-decoration: none; font-weight: bold; color:<?=$primary?>; overflow:hidden; display: block; }

.shortcutmenu { margin: 2px; border: 1px outset; width: 100px; text-align: left; font-size: 10px; }
.shortcutmenu img { margin-right: 5px; margin-left: 5px; }

.subnavtabs { background: url(themes/<?=$theme?>/chrome.png); }
.subnavtabs ul { margin: 0 0 20px 0; }
.subnavtabs li { margin-left: 10px; }
.subnavtabs a { height: 18px; padding: 4px 10px 0; }
.subnavtabs a:link, .subnavtabs a:active, .subnavtabs a:visited { color: <?=$primary?>; }
.subnavtabs li.navtab_active { background: #fff; font-weight: bold; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; }


/*----- Content sections, section widths set up here for layout design -----*/

.content_wrap { margin-top: 15px; }
.container { position: relative; }
.sectitle { width: 100%; }
.secbutton { position: absolute; width: 150px; }
.sectimeline { padding: 0 0 0 150px; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }
.window_aside { float: left; display: inline; width: 165px; margin: 0; }
.window_main { width: inherit; margin: 0 0 0 175px; border-left: 1px dotted gray; padding: 0 0 0 10px;}

.pagetitle { margin: 0 0 10px 0; font-weight: bold; color: <?=$primary?>;  }
.pagetitlesubtext { margin: 0 0 10px 0; }

.newjob a { background: url(img/newjob.jpg) center top no-repeat; display: block; width: 100%; height: 122px; text-indent: -9999px; }
.emrjob a { background: url(img/newemergency.jpg) center top no-repeat; display: block; width: 100%; height: 45px; text-indent: -9999px; }

.newjob a:hover, .emrjob a:hover { background-position: center bottom; }

.window {	-webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; 
-webkit-box-shadow: 0 2px 6px #999; -moz-box-shadow: 0 2px 6px #999; box-shadow: 0 2px 6px #999; }

.window_title { background: url(themes/<?=$theme?>/win_t.gif) repeat-x 0 -1px; margin: 0; padding: 3px 0 0 20px; color: #444; font-size: 1em;  height: 20px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;  
-webkit-border-radius: 8px 8px 0 0 ; -moz-border-radius: 8px 8px 0 0 ; border-radius: 8px 8px 0 0 ; }

.feedfilter a { display: block; margin: 0 0 0 12px; padding: 5px 4px; }
.feed_content a,
.feed_wrap a { color: #484848; }

/*----- Table list styling -----*/

table.list tr.listHeader { background-color: #a0a9a0; vertical-align: top; }
table.list th { color: #fff; }
table.list td { color: #444; }

/*----- Button styling -----*/

.import_file_data .btn_middle {  }
.btn { margin: 0 5px 5px 0; }

/*----- Admin settings list styles -----*/

.linkslist {  }
.linkslist li { padding: 5px; }
.linkslist li.heading { background: #a0a9a0; color: #fff; }


/*----- Prototip styles for shortcut menu, further styling for this is in the prototip.css.php file in the css folder -----*/
	
.shortcuts a { color: #444444; }
.shortcuts a:hover { background: #92bde5; color: #fff; }

/*----- IE7 classes re-adds the rounded corners for ie7-----*/

.ie7 .window { border: none; }
.ie7 .window_title { background: url("themes/classroom/win_t.gif") repeat-x; height: 23px; padding: 0 0 0 20px; line-height: 23px; }
.ie7 .window_title_l { background: url("themes/classroom/win_tl.gif") no-repeat; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 23px; }
.ie7 .window_title_r { background: url("themes/classroom/win_tr.gif") no-repeat; position: absolute; top: 0; right: 0; display: block; width: 12px; height: 23px; }
.ie7 .window_body_l { background: url("themes/classroom/win_l.gif") repeat-y; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 100%; }
.ie7 .window_body_r { background: url("themes/classroom/win_r.gif") repeat-y; position: absolute; top: 0; right: 0; display: block; width: 12px; height: 100%; }
.ie7 .window_foot_wrap { position: relative; display: block; }
.ie7 .window_foot { background: url("themes/classroom/win_b.gif") repeat-x; position: absolute; top: 0; left: 0; display: block; width: 100%; height: 15px; }
.ie7 .window_foot_l { background: url("themes/classroom/win_bl.gif") no-repeat; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 15px; z-index: 9; }
.ie7 .window_foot_r { background: url("themes/classroom/win_br.gif") 2px 0 no-repeat; position: absolute; top: 0; right: 0; display: block; width: 14px; height: 15px; z-index: 9; }

.ie7 .btn { overflow: visible;  }
.ie7 .btn_middle img { margin-top: 0; }

.ie7 #footer { padding: 15px 0; }


/*----- IE8 classes re-adds the rounded corners for ie8-----*/

.ie8 .window { border: none; }
.ie8 .window_title { background: url("themes/classroom/win_t.gif") repeat-x; height: 23px; padding: 0 0 0 20px; line-height: 23px; }
.ie8 .window_title_l { background: url("themes/classroom/win_tl.gif") no-repeat; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 23px; }
.ie8 .window_title_r { background: url("themes/classroom/win_tr.gif") no-repeat; position: absolute; top: 0; right: 0; display: block; width: 12px; height: 23px; }
.ie8 .window_body_l { background: url("themes/classroom/win_l.gif") repeat-y; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 100%; }
.ie8 .window_body_r { background: url("themes/classroom/win_r.gif") repeat-y; position: absolute; top: 0; right: 0; display: block; width: 12px; height: 100%; }
.ie8 .window_foot_wrap { position: relative; display: block; }
.ie8 .window_foot { background: url("themes/classroom/win_b.gif") repeat-x; position: absolute; top: 0; left: 0; display: block; width: 100%; height: 15px; }
.ie8 .window_foot_l { background: url("themes/classroom/win_bl.gif") no-repeat; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 15px; z-index: 9; }
.ie8 .window_foot_r { background: url("themes/classroom/win_br.gif") 2px 0 no-repeat; position: absolute; top: 0; right: 0; display: block; width: 14px; height: 15px; z-index: 9; }

.ie8 .btn { overflow: visible;  }

/*----- Classes that need PIE -----*/
/*
 * Commenting out dependency on CSS3PIE until a stable version is included that do not crash IE
 * Mantis Bug #5157
.window, .window_title, .window_body
{ behavior: url(PIE.php); position: relative;}
*/
