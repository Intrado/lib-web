/* Simplegrey theme */


/* colours 
``````````````````````````````
#222222 dark grey
#efefef light grey
#D14836 pink/red

``````````````````````````````*/

/* basics
``````````````````````````````*/

body { background: #f9f9f9; }
body, table, form, select, input { font-family: 'Helvetica Neue', Helvetica, arial, sans-serif;	/*font-size: 13px;*/ }
a, a:visited { color: #484848; }
a:hover { color: #D14836; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #fff; padding: 20px; border-top: 3px solid #222; }
.banner_logo a { display: block; }

.banner_links { float: right; padding: 12px 13px; }
.banner_links li { border-right: 1px dashed #d7d7d7; }								             
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { display: block; padding: 0 7px; color: #222; font: 12px/15px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.banner_links li a:hover { color: #D14836; text-decoration: none; }
.banner_custname { float: left; display: inline; text-align: right; padding: 0 0 0 15px;
font: 19px/27px 'Helvetica Neue',Helvetica,arial,sans-serif; sans-serif; color: #555; }


/*----- Navigation -----*/

.primary_nav { background: #efefef; padding: 0 20px; border-bottom: 1px solid #e1e1e1; }
.primary_nav li { border-right: 1px solid #e1e1e1; border-left: 1px solid #f7f7f7; }
.primary_nav li:first-of-type { border-left: 1px solid #e5e5e5; border-right: 1px solid #e1e1e1; }

.primary_nav li a { display: block; padding: 12px 17px; color: #222; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.primary_nav a:hover, 
.primary_nav a:active { color: #D14836; } 
.primary_nav .navtab_active a { background: #e5e5e5; color: #D14836; }
		
.navshortcut { display: none; } 

.subnavtabs { background: #f2f2f2; width: auto; list-style-type: none; padding: 15px 20px; border-bottom: 1px solid #e5e5e5; z-index: 99; }
.subnavtabs li { margin: 0; font: 12px/15px 'Helvetica Neue',Helvetica,arial,sans-serif; border-right: 1px dashed #d5d5d5; }
.subnavtabs li:last-of-type { border: none; }
.subnavtabs a { padding: 0 18px; color: #333;	}	
.subnavtabs a:hover,
.subnavtabs .navtab_active a { text-decoration: none; color: #D14836; }
																	  

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: 0 0 20px; padding: 15px 20px; background: #f9f9f9; }
.sectitle { width: 40%; display: none; }
.secbutton { width: 100%; }
.sectimeline { width: 100%; display: none; }
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.newjob { float: left; display: inline; margin: 0 0 20px 0; }
.emrjob { display: none; }

.newjob a { padding: 12px 30px; font: 21px/21px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #FFFFFF; font-size: 21px; border: 1px solid #f5f5f5; }

.newjob a {
  background-color: #ee432e;
  background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #ee432e), color-stop(50%, #c63929), color-stop(50%, #b51700), color-stop(100%, #891100));
  background-image: -webkit-linear-gradient(top, #ee432e 0%, #c63929 50%, #b51700 50%, #891100 100%);
  background-image: -moz-linear-gradient(top, #ee432e 0%, #c63929 50%, #b51700 50%, #891100 100%);
  background-image: -ms-linear-gradient(top, #ee432e 0%, #c63929 50%, #b51700 50%, #891100 100%);
  background-image: -o-linear-gradient(top, #ee432e 0%, #c63929 50%, #b51700 50%, #891100 100%);
  background-image: linear-gradient(top, #ee432e 0%, #c63929 50%, #b51700 50%, #891100 100%);
  -webkit-border-radius: 5px; -moz-border-radius: 5px; -ms-border-radius: 5px; -o-border-radius: 5px; border-radius: 5px;
  -webkit-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4), 0 1px 3px #333333;
  -moz-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4), 0 1px 3px #333333;
  -ms-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4), 0 1px 3px #333333;
  -o-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4), 0 1px 3px #333333;
  box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4), 0 1px 3px #333333;
  text-shadow: 0px -1px 1px rgba(0, 0, 0, 0.8); }
  
.newjob a:hover {
    background-color: #f37873;
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #f37873), color-stop(50%, #db504d), color-stop(50%, #cb0500), color-stop(100%, #a20601));
    background-image: -webkit-linear-gradient(top, #f37873 0%, #db504d 50%, #cb0500 50%, #a20601 100%);
    background-image: -moz-linear-gradient(top, #f37873 0%, #db504d 50%, #cb0500 50%, #a20601 100%);
    background-image: -ms-linear-gradient(top, #f37873 0%, #db504d 50%, #cb0500 50%, #a20601 100%);
    background-image: -o-linear-gradient(top, #f37873 0%, #db504d 50%, #cb0500 50%, #a20601 100%);
    background-image: linear-gradient(top, #f37873 0%, #db504d 50%, #cb0500 50%, #a20601 100%);
    cursor: pointer; }

.newjob a:active {
    background-color: #d43c28;
    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #d43c28), color-stop(50%, #ad3224), color-stop(50%, #9c1500), color-stop(100%, #700d00));
    background-image: -webkit-linear-gradient(top, #d43c28 0%, #ad3224 50%, #9c1500 50%, #700d00 100%);
    background-image: -moz-linear-gradient(top, #d43c28 0%, #ad3224 50%, #9c1500 50%, #700d00 100%);
    background-image: -ms-linear-gradient(top, #d43c28 0%, #ad3224 50%, #9c1500 50%, #700d00 100%);
    background-image: -o-linear-gradient(top, #d43c28 0%, #ad3224 50%, #9c1500 50%, #700d00 100%);
    background-image: linear-gradient(top, #d43c28 0%, #ad3224 50%, #9c1500 50%, #700d00 100%);
    -webkit-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4);
    -moz-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4);
    -ms-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4);
    -o-box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4);
    box-shadow: inset 0px 0px 0px 1px rgba(255, 115, 100, 0.4); }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; }

.window_title { margin: 0; padding: 8px 15px; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.window_body_wrap { padding: 0; }

.window_main { width: 77%;  }

#feeditems { border: 1px solid #f1f1f1; background: #fff; }
#feeditems .content_row { width: auto; margin: 1em; padding: 0 0 1em 0; border-bottom: 1px dashed #f1f1f1; }

#t_zoom a { color: #484848; }
#t_zoom a:hover { color: #D14836; }

#filterby { margin: 0 0 7px 0; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.feedfilter li { padding: 0; }
.feedfilter { background: #f3f3f3; border: 1px solid #ebebeb; }
.feedfilter a { display: block; padding: 7px 8px 5px 12px; border-bottom: 1px dashed #e9e9e9; }
.feedfilter a:hover { background: #e9e9e9; }
.feedfilter img { margin: 0 8px 0 0; }

.feed_item { border-bottom: 1px dashed #ccc; }

.feedtitle { font: 15px/25px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #444; }
.feedtitle a:hover, .feed_title:hover { color: #D14836; text-decoration: none; }
.feed_content a, .feed_item a { color: #484848;}

.list a, a.actionlink { color: #484848; }
.list a:hover, a.actionlink:hover { color: #D14836; }


/*----- Big buttons styling -----*/

.big_btn { float: right; margin-top: -1.5em;}
.big_btn img { display: block; float: left; margin-left: 1em; }


/*----- Footer styling-----*/

#footer { margin: 0 25px; }
#termsinfo, #logininfo { font: bold 9px/14px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #888; }


/*----- Window buttons -----*/
   
button { border: none; background-color: transparent; }
.btn { margin: 0 8px 10px 0; border: none; background: transparent; font-weight: bold; cursor: pointer; }*/
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