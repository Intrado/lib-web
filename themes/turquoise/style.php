/* Turquoise theme */


/* colours 
``````````````````````````````
#058C8E darkturquoise
#279C9E lightturquoise
#FD5701 orange

``````````````````````````````*/

/* basics
``````````````````````````````*/

body { background: #fff; }
body, table, form, select, input { font-family: 'Helvetica Neue', Helvetica, arial, sans-serif;	/*font-size: 13px;*/ }
a { color: #484848; }
a:hover { color: #058C8E; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #fff; height: 125px; }
.banner_logo { float: none; position: absolute; top: 65px; left: 20px; }
.banner_logo a { display: block; }

.banner_links_wrap { background: #101010; float: none; display: block; height: 35px; }
.banner_links { float: right; padding: 10px 13px; }
.banner_links li { border-right: 1px solid #666; }									             
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { display: block; padding: 0 7px; color: #ccc; font: 12px/15px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.banner_links li a:hover { color: #f1f1f1; }
.banner_custname { top: 70px; right: 20px; text-align: right; font: 19px/27px 'Helvetica Neue',Helvetica,arial,sans-serif; sans-serif; color: #202020;  }


/*----- Navigation -----*/

.primary_nav { background: #058C8E; margin: 10px 0 0; padding: 15px 20px; }
.primary_nav li { margin-right: 1em; }

.primary_nav li a { display: block; padding: 7px 14px; color: #fff; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; border-bottom: #F6D97D; 
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.primary_nav a:hover, 
.primary_nav a:active { background: #279C9E; color: #fff; 
-webkit-box-shadow: 0px 1px 2px #333; -moz-box-shadow: 0px 1px 2px #333; box-shadow: 0px 1px 2px #333; } 
.primary_nav .navtab_active a { background: #FD5701; color: #fff; 
-webkit-box-shadow: 0px 1px 2px #333; -moz-box-shadow: 0px 1px 2px #333; box-shadow: 0px 1px 2px #333; }
		
.navshortcut { background-color: #279C9E; color: #fff; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif;
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 1px 2px #333; -moz-box-shadow: 0px 1px 2px #333; box-shadow: 0px 1px 2px #333; }
.shortcutmenu { padding: 7px 7px 7px 11px; cursor: pointer; }
.shortcutmenu img { padding: 2px; } 

.subnavtabs { background: #fff; width: auto; list-style-type: none; padding: 0 20px; z-index: 99; }
.subnavtabs li { margin: 0; font: 11px/11px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.subnavtabs a { padding: 15px 18px; font-weight: bold; color: #058C8E;	}	
.subnavtabs a:hover { background: #f5f5f5; text-decoration: none; }	
.subnavtabs .navtab_active a { background: #fff url('img/aro.png') top center no-repeat; text-decoration: none; color: #FD5701; }
																	  

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: 0 0 20px; padding: 15px 20px; background: #f5f5f5; 
-webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.sectitle { width: 40%; }
.secbutton { width: 60%; }
.sectimeline { width: 100%; }
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.newjob, .emrjob { float: right; display: inline; margin: 5px 0 20px 15px; }

.newjob a, .emrjob a { padding: 10px 14px; }
.newjob a, .emrjob a { color: #FFFFFF; font-size: 15px; font-weight: bold; border: 1px solid #f5f5f5; 
 -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 0px 2px #444; -moz-box-shadow: 0px 0px 2px #444; box-shadow: 0px 0px 2px #444; 
background-color: #FD5701;
  background-image: -webkit-gradient(linear, left top, left bottom, from(#FD5701), to(#DA4D01)); 
  background-image: -webkit-linear-gradient(top, #FD5701, #DA4D01); 
  background-image:    -moz-linear-gradient(top, #FD5701, #DA4D01); 
  background-image:     -ms-linear-gradient(top, #FD5701, #DA4D01); 
  background-image:      -o-linear-gradient(top, #FD5701, #DA4D01); 
  background-image:         linear-gradient(top, #FD5701, #DA4D01); }

.newjob a:hover, .emrjob a:hover {
-webkit-box-shadow: 0px 1px 4px #333; -moz-box-shadow: 0px 1px 4px #333; box-shadow: 0px 1px 4px #333; 
  background-color: #FE742A;
  background-image: -webkit-gradient(linear, left top, left bottom, from(#FE742A), to(#FD5701)); 
  background-image: -webkit-linear-gradient(top, #FE742A, #FD5701); 
  background-image:    -moz-linear-gradient(top, #FE742A, #FD5701); 
  background-image:     -ms-linear-gradient(top, #FE742A, #FD5701); 
  background-image:      -o-linear-gradient(top, #FE742A, #FD5701); 
  background-image:         linear-gradient(top, #FE742A, #FD5701); }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; border-bottom: 2px solid #dbdbdb; }

.window_title { background: #279C9E; margin: 0; padding: 8px 15px; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #fff; border-bottom: 1px solid #058C8E; }

.window_body_wrap { background: #fff; }

#t_zoom a { color: #484848; }
#t_zoom a:hover { color: #FD5701; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

#feeditems .content_row, .feed_item { border-bottom: 1px dashed #ccc; }

.feedtitle { font: bold 14px/21px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.feedtitle a:hover, .feed_title:hover { color: #FD5701; text-decoration: none; }
.feed_content a, .feed_item a { color: #484848;}

.list a, a.actionlink { color: #484848; }
.list a:hover, ul.actionlinks li a:hover { color: #FD5701; }


/*----- Big buttons styling -----*/

.big_btn { float: right; margin-top: -1.5em;}
.big_btn img { display: block; float: left; margin-left: 1em; }


/*----- Footer styling-----*/

#footer { margin: 0 25px; }
#termsinfo, #logininfo { font: bold 9px/14px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #888; }


/*----- Window buttons -----*/
   
button { border: none; background-color: transparent; }
.btn { margin: .25em .5em .25em 0; border: none; background: transparent; font-weight: bold; cursor: pointer; }*/
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
.ie7 div.window_aside { margin: 0 1.8% 0 0; }


/*----- Classes that need PIE -----*/

.banner_links li a, .navtabs a, .navshortcut, .newjob a, .emrjob a, .btn
{ behavior: url(PIE.php); position: relative;}
