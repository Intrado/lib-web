/*----- New IU theme, uses css.inc.php as basic layout example can be seen in example.css -----*/

/* colours 
``````````````````````````````
#3399ff lighter blue
#346799 mid blue
#26477d dark blue
#2a4470 darker blue

``````````````````````````````*/

/*----- basic colours for theme -----*/
body { background: #e5e5e5 url(themes/newui/bodybg.jpg) repeat; }
a, a:visited { color: #484848; }
a:hover { color: #13a545; }


/*----- Banner, adds in the green background and moves the client name to sit next to the logo image -----*/

.banner { background: #346799 url(themes/newui/themebg.jpg) repeat; padding: 0 25px 15px 25px; border-top: 4px solid #222; }
.banner_logo { margin: 34px 0 0; }
.banner_logo h1 { display: block; margin: 0; color: #f5f5f5; text-shadow: 0 1px 0 #444; }
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

.primary_nav { background: #346799 url(themes/newui/themebg.jpg) repeat; width: 100%; padding: 20px 0 0; border-bottom: 1px solid #2a4470; }
  
.navshortcut { display: none; }
	
.navtabs { margin: 0 0 0 25px; }
.navtabs li { margin: 0 2px -1px 0;}
.navtabs li a { display: block; padding: 14px 18px; font: 16px Verdana, arial, sans-serif; color: #fff; text-decoration: none; text-shadow: 0 1px 0 #444; border: 1px solid transparent;
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.navtabs li a:hover { border: 1px solid #2a4470; 
-webkit-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); -moz-box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); box-shadow: inset 0px 1px 0px rgba(255,255,255,0.5); }
.navtabs li.navtab_active a { background: #f7f7f7; color: #444; text-shadow: 0 1px 0 #fff; border: 1px solid #2a4470; border-bottom: 1px solid #f7f7f7; }

.subnavtabs { background: #f7f7f7; padding: 5px 0; border-bottom: 1px solid #b6b6b6; }
.subnavtabs li { margin: 5px 0 5px 25px; }
.subnavtabs a { display: block; margin: 0; padding: 5px 11px; font: 14px Verdana, arial, sans-serif; color: #26477d; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.subnavtabs a:hover,
.subnavtabs a:active,
.subnavtabs .navtab_active a { background: #e2e2e2; color: #242424; text-decoration: none; }


/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { position: relative; margin: 10px 0 0 0; padding: 15px; }
.sectitle { width: 40%; }
.secbutton { position: absolute; top: -65px; right: 15px; }
.sectimeline { display: none; }

.pagetitle { font-family: Verdana, Arial, sans-serif; margin: 0.5em 0; color: #0d8336; }

.emrjob { display: none; }

.newjob a { padding: 5px 10px; color: #fff; font: bold 16px/21px sans-serif, Arial; text-shadow: 0 1px 0 #333; border: 1px solid #a9320d;
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
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}

.newjob a:before { content: '+'; padding: 1px 4px; margin: 0 5px 0 0; font-size: 21px; }
  
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

.window { padding: 8px; border: none;  
	-webkit-box-shadow: 0px 2px 8px 0px #777; 
	   -moz-box-shadow: 0px 2px 8px 0px #777; 
	        box-shadow: 0px 2px 8px 0px #777;
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


/*----- Classes that need PIE -----*/

.navshortcut, .navtabs a, .newjob a, .emrjob a, .banner_links, .banner_logo a, .subnavtabs a, .btn, .window, .window_title, .window_body
{ behavior: url(PIE.php); position: relative;}


