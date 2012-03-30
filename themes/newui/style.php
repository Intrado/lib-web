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
        
.window_title { margin: 0; padding: 10px 15px; font-size: 17px; font-weight: bold; color: #242424; text-shadow: 0 1px 0 #fff; }

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


/*----- Prototip styles for shortcut menu, not actually used in this theme -----*/
	
.shortcuts a, .shortcuts a:visited { color: #26477d; }
.shortcuts a:hover { background: #26477d; color: #fff; }


/*----- Classes that need PIE -----*/

.navshortcut, .navtabs a, .newjob a, .emrjob a, .banner_links, .banner_logo a, .subnavtabs a, .btn, .window, .window_title_wrap, .window_body
{ behavior: url(PIE.php); position: relative;}


