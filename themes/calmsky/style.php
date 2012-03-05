/* CALM SKY THEME CSS */


/* colours 
``````````````````````````````
#049CDB lightblue
#0064CD darkblue

``````````````````````````````*/

/* basics
``````````````````````````````*/

body { background: #fff; }
body, table, form, select, input { font-family: 'Helvetica Neue', Helvetica, arial, sans-serif;	/*font-size: 13px;*/ }
a { color: #484848; }
a:hover { color: #0064CD; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #fff; padding: 10px 25px; height: 75px; }
.banner_logo a { background: #fff; display: block; padding: 0 0 10px 0; }

.banner_links { padding: 20px 0 0; }									             
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { background: #ccc; display: block; margin: 0 0 0 8px; padding: 8px 12px; color: #333; font: 12px/15px 'Helvetica Neue',Helvetica,arial,sans-serif; 
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.banner_links li a:hover { background: #0064CD; color: #fff; text-decoration: none; }
.banner_custname { top: 63px; left: 25px; text-align: right; font: 19px/27px 'Helvetica Neue',Helvetica,arial,sans-serif; sans-serif; color: #00438A;  }


/*----- Navigation -----*/

.primary_nav { background: #dbdbdb; margin: 10px 25px 0 25px; padding: 15px;
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.primary_nav li { margin-right: 1em; }

.primary_nav li a { display: block; padding: 7px 14px; color: #666; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; border: 1px solid #c9c9c9; 
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 1px 1px #e4e4e4; -moz-box-shadow: 0px 1px 1px #e4e4e4; box-shadow: 0px 1px 1px #e4e4e4; }
.primary_nav a:hover,
.primary_nav a:active { background: #e1e1e1; border: 1px solid #dbdbdb;
-webkit-box-shadow: 0px 0px 2px #444; -moz-box-shadow: 0px 0px 2px #444; box-shadow: 0px 0px 2px #444; }  
.primary_nav .navtab_active a { background: #62CFFC; color: #fff; border: 1px solid #dbdbdb; 
-webkit-box-shadow: inset 0px 0px 2px #444; -moz-box-shadow: inset 0px 0px 2px #444; box-shadow: inset 0px 0px 2px #444; }
		
.navshortcut { background-color: #F3B329; color: #0F4A74; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif;
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
  background-image: -webkit-gradient(linear, left top, left bottom, from(#F6D97D), to(#F3B329)); 
  background-image: -webkit-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:    -moz-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:     -ms-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:      -o-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:         linear-gradient(top, #F6D97D, #F3B329);
  -webkit-box-shadow: inset 0px 0px 2px #444; -moz-box-shadow: inset 0px 0px 2px #444; box-shadow: inset 0px 0px 2px #444; }
.shortcutmenu { padding: 7px 7px 7px 11px; cursor: pointer; }
.shortcutmenu img { padding: 2px; } 

.subnavtabs { background-color: #e5e5e5; width: auto; list-style-type: none; margin: 0 25px; padding: 0; z-index: 99; }
  
.subnavtabs li { margin: 0; font: 11px/11px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.subnavtabs a { padding: 15px 18px; font-weight: bold; color: #286FBE;	}	
.subnavtabs a:hover { background-color: #dedede; text-decoration: none; }	
.subnavtabs a:focus,
.subnavtabs a:active,
.subnavtabs .navtab_active a { background-color: #f5f5f5; text-decoration: none; }
																	  

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: 0 25px 20px 25px; padding: 15px; background: #f5f5f5; 
-webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.sectitle { width: 40%; }
.secbutton { width: 60%; }
.sectimeline { width: 100%; }
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.newjob, .emrjob { float: right; display: inline; margin: 0 0 15px 15px; }
.newjob a, .emrjob a { padding: 10px 14px; }
.newjob a, .emrjob a { background-color: #049CDB; color: #FFFFFF; font-size: 15px; text-shadow: 0 -1px 0 #222; border: 1px solid #00478F;
  background-image: -webkit-gradient(linear, left top, left bottom, from(#049CDB), to(#0064CD)); 
  background-image: -webkit-linear-gradient(top, #049CDB #0064CD); 
  background-image:    -moz-linear-gradient(top, #049CDB, #0064CD); 
  background-image:     -ms-linear-gradient(top, #049CDB, #0064CD); 
  background-image:      -o-linear-gradient(top, #049CDB, #0064CD); 
  background-image:         linear-gradient(top, #049CDB, #0064CD); 
 -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: inset 0px 0px 1px #ffffff; -moz-box-shadow: inset 0px 0px 1px #ffffff; box-shadow: inset 0px 0px 1px #ffffff; }
.newjob a:hover, .emrjob a:hover { background: #0064CD; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; }

.window_title { margin: 0 15px; padding: 10px 0; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.window_body_wrap { background: #fff; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
-webkit-box-shadow: inset 0px 0px 2px #777; -moz-box-shadow: inset 0px 0px 2px #777; box-shadow: inset 0px 0px 2px #777; }

#t_zoom a { color: #484848; }
#t_zoom a:hover { color: #0064CD; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

#feeditems .content_row, .feed_item { border-bottom: 1px dashed #ccc; }

.feedtitle { font: bold 14px/21px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.feedtitle a:hover, .feed_title:hover { color: #0064CD; text-decoration: none; }
.feed_content a, .feed_item a { color: #484848;}

.list a, a.actionlink { color: #484848; }
.list a:hover, a.actionlink:hover { color: #0064CD; }


/*----- Big buttons styling -----*/

.big_btn { float: right; margin-top: -1.5em;}
.big_btn img { display: block; float: left; margin-left: 1em; }


/*----- Footer styling-----*/

#footer { margin: 0 25px; }
#termsinfo, #logininfo { font: bold 9px/14px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #888; }


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
.shortcuts a:hover { background: #62CFFC; color: #fff; }


/*----- ie7 styles -----*/
.ie7 div.window_aside { margin: 0 1.8% 0 0; }


/*----- Classes that need PIE -----*/

.banner_links li a, .navtabs a, .navshortcut, .newjob a, .emrjob a, .btn
{ behavior: url(PIE.php); position: relative;}
