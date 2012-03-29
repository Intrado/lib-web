/*----- New IU theme, uses css.inc.php as basic layout example can be seen in example.css -----*/

/*----- basic colours for theme -----*/
body { background: #ededed; }
a, a:visited { color: #484848; }
a:hover { color: #13a545; }


/*----- Banner, adds in the green background and moves the client name to sit next to the logo image -----*/

.banner { padding: 0 25px 15px 25px; border-top: 4px solid #222; background-color: #0d8336; }
.banner_logo { margin: 15px 0 0; }
.banner_logo a { background: #fff; padding: 6px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}
.banner_links { background: #363636; float: right; display: inline; padding: 5px 3px 8px 3px; list-style-type: none; 
-webkit-border-radius: 0 0 8px 8px; -moz-border-radius: 0 0 8px 8px; border-radius: 0 0 8px 8px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.banner_links li { padding: 0 8px; border-right: 1px dashed #bdbdbd; }
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { color: #e6e6e6; }
.banner_links li a:hover { color: #13a545; text-decoration: none; }
.banner_custname { top: 40px; right: 25px; text-align: right; font-size: 21px; color: #f2f2f2; text-shadow: 0 1px 0 rgba(0,0,0,0.6); }


/*----- Navigation, adds orange bg colour and styles up the primary links and navshortcut dropdown -----*/

.primary_nav { width: 100%; padding: 10px 0; background-color: #f5b75e;
  background-image: -webkit-gradient(linear, left top, left bottom, from(#f5b75e), to(#da6c17)); 
  background-image: -webkit-linear-gradient(top, #f5b75e, #da6c17); 
  background-image:    -moz-linear-gradient(top, #f5b75e, #da6c17); 
  background-image:     -ms-linear-gradient(top, #f5b75e, #da6c17); 
  background-image:      -o-linear-gradient(top, #f5b75e, #da6c17); 
  background-image:         linear-gradient(top, #f5b75e, #da6c17); 
  -webkit-box-shadow: 0px 1px 4px #333; -moz-box-shadow: 0px 1px 4px #333; box-shadow: 0px 1px 4px #333; }
  
.navshortcut { background: #fac478; background: rgba(255,255,255,0.2); margin: 0 25px 0 0; color: #fff; font: 15px Verdana, arial, sans-serif; font-size: 13px; cursor: pointer;
-webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 0 2px #333; -moz-box-shadow: 0px 0 2px #333; box-shadow: 0px 0 2px #333; }
.shortcutmenu { padding: 7px 10px; }
.shortcutmenu img { padding: 0 0 0 5px; }
	
.navtabs { margin: 0 0 0 25px; }
.navtabs li { margin-right: 15px;}
.navtabs a { display: block; padding: 8px 17px; font: 15px Verdana, arial, sans-serif; color: #fff; text-decoration: none; 
-webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.navtabs li a:hover, 
.navtabs li a:active,  
.navtabs li.navtab_active a { background: #AE6C0B; background: rgba(0,0,0,0.3); 
-webkit-box-shadow: inset 0px 1px 3px #333; -moz-box-shadow: inset 0px 1px 3px #333; box-shadow: inset 0px 1px 3px #333; }

.subnavtabs { background: #d9d9d9; margin: 0 0 15px 0; padding: 10px 0; 
-webkit-box-shadow: 0px 1px 4px #333; -moz-box-shadow: 0px 1px 4px #333; box-shadow: 0px 1px 4px #333;}
.subnavtabs li { margin: 0 0 0 25px; }
.subnavtabs a { display: block; margin: 0; padding: 5px 11px; font: 13px Verdana, arial, sans-serif; color: #494949; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.subnavtabs a:hover,
.subnavtabs a:active,
.subnavtabs .navtab_active a { background: #c4c4c4; color: #242424; text-decoration: none; 
-webkit-box-shadow: inset 0px 1px 3px #333; -moz-box-shadow: inset 0px 1px 3px #333; box-shadow: inset 0px 1px 3px #333;}


/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin-top: 15px; }
.sectitle { width: 40%; }
.secbutton { width: 60%; }
.sectimeline { width: 100%; }

.pagetitle { font-family: Verdana, Arial, sans-serif; margin: 0.5em 0; color: #0d8336; }

.newjob, .emrjob { float: right; display: inline; margin: 0 0 15px 15px; }

.newjob a, .emrjob a { padding: 12px 10px; color: #fff; text-transform: uppercase; font: bold 14px sans-serif, Arial; 
border: 1px solid #494949; text-shadow: 0 1px 0 #333; opacity: 0.8; 
-webkit-box-shadow: inset 0px 0px 2px #fff; -moz-box-shadow: inset 0px 0px 2px #fff; box-shadow: inset 0px 0px 2px #fff; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}

.newjob a:before { background: rgba(0,0,0,0.4); content: '+'; padding: 1px 4px; margin: 0 5px 0 0; }
.newjob a { background-color: #598527;  
  background-image: -webkit-gradient(linear, left top, left bottom, from(#c7c157), to(#598527)); 
  background-image: -webkit-linear-gradient(top, #c7c157, #598527); 
  background-image:    -moz-linear-gradient(top, #c7c157, #598527); 
  background-image:     -ms-linear-gradient(top, #c7c157, #598527); 
  background-image:      -o-linear-gradient(top, #c7c157, #598527); 
  background-image:         linear-gradient(top, #c7c157, #598527); }

.emrjob a:before { background: rgba(0,0,0,0.4); content: '!'; padding: 1px 6px; margin: 0 5px 0 0; }
.emrjob a { background-color: #ed1c24; 
  background-image: -webkit-gradient(linear, left top, left bottom, from(#f7941d), to(#ed1c24)); 
  background-image: -webkit-linear-gradient(top, #f7941d, #ed1c24); 
  background-image:    -moz-linear-gradient(top, #f7941d, #ed1c24); 
  background-image:     -ms-linear-gradient(top, #f7941d, #ed1c24); 
  background-image:      -o-linear-gradient(top, #f7941d, #ed1c24); 
  background-image:         linear-gradient(top, #f7941d, #ed1c24); }
  
.newjob a:hover, .emrjob a:hover { opacity: 1; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { -webkit-box-shadow: inset 0px 0px 2px #666; -moz-box-shadow: inset 0px 0px 2px #666; box-shadow: inset 0px 0px 2px #666; padding: 8px; 
-webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.window_title { background: #C4C4C4; margin: 0; padding: 10px 15px; font-size: 17px; font-weight: normal; color: #242424; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
-webkit-box-shadow: inset 0px 1px 3px #333; -moz-box-shadow: inset 0px 1px 3px #333; box-shadow: inset 0px 1px 3px #333; }

#t_legend span, #t_zoom a { color: #484848; }
#t_zoom a:hover { color: #3E693F; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

.feedtitle a, .feed_content a, .feed_item a, .actionlink  { color: #444; }
.feedtitle a:hover, .feed_title:hover, ul.actionlinks li a:hover { color: #13a545; }

#logininfo, #termsinfo { line-height: 13px; }


/*----- Buttons, changes to the basic buttons featured across the site -----*/

.btn { background: #b2b2b2; margin: 0 8px 5px 0; padding: 4px 0; border: none; color: #f1f1f1; font-weight: bold; cursor: pointer; border: 1px solid #8a8a8a; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: inset 0px 0px 2px #fff; -moz-box-shadow: inset 0px 0px 2px #fff; box-shadow: inset 0px 0px 2px #fff; }
.btn:hover { background: #0d8336; color: #fff; border: 1px solid #053315; 
-webkit-box-shadow: inset 0px 1px 3px #053315; -moz-box-shadow: inset 0px 1px 3px #053315; box-shadow: inset 0px 1px 3px #053315; }
.btn_left, .btn_middle, .btn_right { background: none; }

/*----- table overrides for list styles -----*/

table.list { width: 100%; margin: 0; }

.bottomBorder { border-bottom: 1px solid #ccc; }
.windowRowHeader { color: #484848; width: 115px; }


/*----- Prototip styles for shortcut menu, further styling for this is in the prototip.css.php file in the css folder -----*/
	
.shortcuts a, .shortcuts a:visited { color: #3E693F; }
.shortcuts a:hover { background: #3E693F; color: #fff; }

/*----- IE7 classes -----*/

.ie7 div.secbutton { width: 59.75%; }

/*----- Classes that need PIE -----*/

.navshortcut, .navtabs a, .newjob a, .emrjob a, .banner_links, .banner_logo a, .subnavtabs a, .btn, .window, .window_title, .window_body
{ behavior: url(PIE.php); position: relative;}


