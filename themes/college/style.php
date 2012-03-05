/* COLLEGE THEME CSS */


/* colours 
``````````````````````````````
#F3B329 (orange)
#F6D97D (mid orange)
#FCF4DC (pale orange)
#286FBE (text blue)
#0F4A74 (dark blue)
#0E486E (darker blue)
#F2F0E4 (background)
#ECE9D8 (darker background)

``````````````````````````````*/

/* basics
``````````````````````````````*/

body { background: #F2F0E4; }
body, table, form, select, input { font-family: 'Helvetica Neue', Helvetica, arial, sans-serif;	/*font-size: 13px;*/ }
a { color: #484848; }
a:hover { color: #286FBE; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #0F4A74; padding: 20px 25px; }
.banner_logo a { background: #fff; display: block; padding: 6px; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.banner_logo a { padding: .5em; background-color: #fff; opacity: .8;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
-webkit-transition: all 0.3s ease-out; 
-moz-transition: all 0.3s ease-out; 
-ms-transition: all 0.3s ease-out; 
-o-transition: all 0.3s ease-out;  
transition: all 0.3s ease-out; }
										             
.banner_logo a:hover {  opacity: 1;
-webkit-box-shadow: 0px 0px 12px #fff; -moz-box-shadow: 0px 0px 12px #fff; box-shadow: 0px 0px 12px #fff;  }
										             
.banner_links li { padding: 0 8px; border-right: 1px solid #F3B329; }
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { color: #e6e6e6; }
.banner_links li a:hover { color: #F3B329; text-decoration: none; }
.banner_custname { top: 40px; right: 25px; text-align: right; font: 18px 'Helvetica Neue',Helvetica,arial,sans-serif; sans-serif; color: #f2f2f2;  }


/*----- Navigation -----*/

.primary_nav { background: #0F4A74; margin: 0 0 25px 0; padding: 0 25px; }
.primary_nav li { margin-right: 1em; }

.primary_nav li a { display: block; padding: 7px 14px; color: #F3B329; font: 15px 'Helvetica Neue',Helvetica,arial,sans-serif; border-bottom: #F6D97D; 
-webkit-border-radius: 4px 4px 0 0; -moz-border-radius: 4px 4px 0 0; border-radius: 4px 4px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.primary_nav a:hover, 
.primary_nav a:active, 
.primary_nav .navtab_active a { background: #F2F0E4; color: #0E486E; 
-webkit-box-shadow: 0px -1px 2px #FCF4DC; -moz-box-shadow: 0px -1px 2px #FCF4DC; box-shadow: 0px -1px 2px #FCF4DC; }
		
.navshortcut { background-color: #F3B329; color: #0F4A74; font: bold 13px 'Helvetica Neue',Helvetica,arial,sans-serif;
-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
  background-image: -webkit-gradient(linear, left top, left bottom, from(#F6D97D), to(#F3B329)); 
  background-image: -webkit-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:    -moz-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:     -ms-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:      -o-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:         linear-gradient(top, #F6D97D, #F3B329);}
.shortcutmenu { padding: 5px 7px 5px 11px; cursor: pointer; }
.shortcutmenu img { padding: 2px; } 

.subnavtabs { background-color: #F6D97D; width: auto; list-style-type: none; margin: 20px 25px 0 25px; padding: 0; z-index: 99;  
-webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; border-radius: 5px 5px 0 0;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
background-image: -webkit-gradient(linear, left top, left bottom, from(#FCF4DC), to(#F6D97D)); 
  background-image: -webkit-linear-gradient(top, #FCF4DC #F6D97D); 
  background-image:    -moz-linear-gradient(top, #FCF4DC, #F6D97D); 
  background-image:     -ms-linear-gradient(top, #FCF4DC, #F6D97D); 
  background-image:      -o-linear-gradient(top, #FCF4DC, #F6D97D); 
  background-image:         linear-gradient(top, #FCF4DC, #F6D97D); 
  -webkit-box-shadow: 0px -1px 3px #666; -moz-box-shadow: 0px -1px 3px #666; box-shadow: 0px -1px 3px #666;}
  
.subnavtabs li { font: 11px/11px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.subnavtabs a { margin: 0 8px 0 0; padding: 15px 8px; font-weight: bold; color: #286FBE; 
											-webkit-transition: color 0.3s ease-out;  
										     -moz-transition: color 0.3s ease-out;  
										      -ms-transition: color 0.3s ease-out;  
										       -o-transition: color 0.3s ease-out;  
										          transition: color 0.3s ease-out;	}	
		
.subnavtabs a:hover,
.subnavtabs a:focus,
.subnavtabs a:active				{ color: #0E486E; }
.subnavtabs .navtab_active a { background: url('img/nav_arrow.png') no-repeat bottom center; color: #0F4A74; }
			
.nav_shortcuts { float: right; margin-right: 2em; padding: 5px 8px; color: #0F4A74; font-weight: bold; border: 1px solid #0F4A74;
-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
																	  
  background-color: #F6D97D;
  background-image: -webkit-gradient(linear, left top, left bottom, from(#F6D97D), to(#F3B329)); 
  background-image: -webkit-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:    -moz-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:     -ms-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:      -o-linear-gradient(top, #F6D97D, #F3B329); 
  background-image:         linear-gradient(top, #F6D97D, #F3B329);

}

.nav_shortcuts img { padding: 0 0 0 5px; }
.nav_shortcuts_menu { display: none; }

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: -4px 25px 20px 25px; padding: 15px; background: #fff; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 1px 3px #666; -moz-box-shadow: 0px 1px 3px #666; box-shadow: 0px 1px 3px #666;}
.sectitle { width: 40%; }
.secbutton { width: 60%; }
.sectimeline { width: 100%; display: none; } /* hide the timeline for this theme */
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.newjob, .emrjob { float: right; display: inline; margin: 0 0 15px 15px; }
.newjob a, .emrjob a { text-indent: -9999px; width: 137px; height: 30px; padding: 12px 0 0; }
.newjob a { background: url(../img/themes/college/easystart.png) left top no-repeat; }
.emrjob a { background: url(../img/themes/college/emergency.png) left top no-repeat; }
.newjob a:hover, .emrjob a:hover { background-position: right bottom; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; }

.window_title_wrap { background: #dbdbdb; 
-webkit-border-radius: 4px 4px 0 0; -moz-border-radius: 4px 4px 0 0; border-radius: 4px 4px 0 0; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.window_title { margin: 0 15px; padding: 10px 0; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; border-bottom: 1px solid #dbdbdb; }

.window_body_wrap { border: 1px solid #dbdbdb; }

.big_btn { float: right; margin-top: -1.5em;}
.big_btn img { display: block; float: left; margin-left: 1em; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

#t_legend span, #t_zoom a { color: #484848; }
#t_zoom a:hover { color: #286FBE; }

.feedtitle a, .feed_content a, .feed_item a, .actionlink { color: #484848; }
.feedtitle a:hover, .actionlink:hover, .feed_title:hover { color: #286FBE; }


/* +----------------------------------------------------------------+
   | button                                                         |
   +----------------------------------------------------------------+ */
   
   
button { border: none; background-color: transparent; }
.btn { margin: .25em .5em; border: none; background: transparent; font-weight: bold; cursor: pointer; }*/
.btn_wrap { white-space: nowrap; position: relative; } 
.btn_hide { position: absolute; left: -9999px; top: -9999px; }



/* +----------------------------------------------------------------+
   | bootstrap buttons and form elements                            |
   +----------------------------------------------------------------+ */

/* cheeky bit - twitter bootstrap buttons ... */
.btn.danger,
.alert-message.danger,
.btn.danger:hover,
.alert-message.danger:hover,
.btn.error,
.alert-message.error,
.btn.error:hover,
.alert-message.error:hover,
.btn.success,
.alert-message.success,
.btn.success:hover,
.alert-message.success:hover,
.btn.info,
.alert-message.info,
.btn.info:hover,
.alert-message.info:hover {
  color: #ffffff;
}
.btn.danger,
.alert-message.danger,
.btn.error,
.alert-message.error {
  background-color: #c43c35;
  background-repeat: repeat-x;
  background-image: -khtml-gradient(linear, left top, left bottom, from(#ee5f5b), to(#c43c35));
  background-image: -moz-linear-gradient(top, #ee5f5b, #c43c35);
  background-image: -ms-linear-gradient(top, #ee5f5b, #c43c35);
  background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #ee5f5b), color-stop(100%, #c43c35));
  background-image: -webkit-linear-gradient(top, #ee5f5b, #c43c35);
  background-image: -o-linear-gradient(top, #ee5f5b, #c43c35);
  background-image: linear-gradient(top, #ee5f5b, #c43c35);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ee5f5b', endColorstr='#c43c35', GradientType=0);
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  border-color: #c43c35 #c43c35 #882a25;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
}
.btn.success, .alert-message.success {
  background-color: #57a957;
  background-repeat: repeat-x;
  background-image: -khtml-gradient(linear, left top, left bottom, from(#62c462), to(#57a957));
  background-image: -moz-linear-gradient(top, #62c462, #57a957);
  background-image: -ms-linear-gradient(top, #62c462, #57a957);
  background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #62c462), color-stop(100%, #57a957));
  background-image: -webkit-linear-gradient(top, #62c462, #57a957);
  background-image: -o-linear-gradient(top, #62c462, #57a957);
  background-image: linear-gradient(top, #62c462, #57a957);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#62c462', endColorstr='#57a957', GradientType=0);
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  border-color: #57a957 #57a957 #3d773d;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
}
.btn.info, .alert-message.info {
  background-color: #339bb9;
  background-repeat: repeat-x;
  background-image: -khtml-gradient(linear, left top, left bottom, from(#5bc0de), to(#339bb9));
  background-image: -moz-linear-gradient(top, #5bc0de, #339bb9);
  background-image: -ms-linear-gradient(top, #5bc0de, #339bb9);
  background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #5bc0de), color-stop(100%, #339bb9));
  background-image: -webkit-linear-gradient(top, #5bc0de, #339bb9);
  background-image: -o-linear-gradient(top, #5bc0de, #339bb9);
  background-image: linear-gradient(top, #5bc0de, #339bb9);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#5bc0de', endColorstr='#339bb9', GradientType=0);
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  border-color: #339bb9 #339bb9 #22697d;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
}
.btn {
  cursor: pointer;
  display: inline-block;
  background-color: #e6e6e6;
  background-repeat: no-repeat;
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), color-stop(25%, #ffffff), to(#e6e6e6));
  background-image: -webkit-linear-gradient(#ffffff, #ffffff 25%, #e6e6e6);
  background-image: -moz-linear-gradient(top, #ffffff, #ffffff 25%, #e6e6e6);
  background-image: -ms-linear-gradient(#ffffff, #ffffff 25%, #e6e6e6);
  background-image: -o-linear-gradient(#ffffff, #ffffff 25%, #e6e6e6);
  background-image: linear-gradient(#ffffff, #ffffff 25%, #e6e6e6);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#e6e6e6', GradientType=0);
  padding: 5px 14px 6px;
  text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
  color: #333;
  font-size: 13px;
  line-height: normal;
  border: 1px solid #ccc;
  border-bottom-color: #bbb;
  -webkit-border-radius: 4px;
  -moz-border-radius: 4px;
  border-radius: 4px;
  -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
  -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
  -webkit-transition: 0.1s linear all;
  -moz-transition: 0.1s linear all;
  transition: 0.1s linear all;
}
.btn:hover {
  background-position: 0 -15px;
  color: #333;
  text-decoration: none;
}
.btn.primary {
  color: #fff;
  background-color: #0064cd;
  background-repeat: repeat-x;
  background-image: -khtml-gradient(linear, left top, left bottom, from(#049cdb), to(#0064cd));
  background-image: -moz-linear-gradient(top, #049cdb, #0064cd);
  background-image: -ms-linear-gradient(top, #049cdb, #0064cd);
  background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #049cdb), color-stop(100%, #0064cd));
  background-image: -webkit-linear-gradient(top, #049cdb, #0064cd);
  background-image: -o-linear-gradient(top, #049cdb, #0064cd);
  background-image: linear-gradient(top, #049cdb, #0064cd);
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#049cdb', endColorstr='#0064cd', GradientType=0);
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
  border-color: #0064cd #0064cd #003f81;
  border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
}
.btn:active {
  -webkit-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25), 0 1px 2px rgba(0, 0, 0, 0.05);
  -moz-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25), 0 1px 2px rgba(0, 0, 0, 0.05);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25), 0 1px 2px rgba(0, 0, 0, 0.05);
}
.btn.disabled {
  cursor: default;
  background-image: none;
  filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
  filter: alpha(opacity=65);
  -khtml-opacity: 0.65;
  -moz-opacity: 0.65;
  opacity: 0.65;
  -webkit-box-shadow: none;
  -moz-box-shadow: none;
  box-shadow: none;
}
.btn[disabled] {
  cursor: default;
  background-image: none;
  filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
  filter: alpha(opacity=65);
  -khtml-opacity: 0.65;
  -moz-opacity: 0.65;
  opacity: 0.65;
  -webkit-box-shadow: none;
  -moz-box-shadow: none;
  box-shadow: none;
}
.btn.large {
  font-size: 16px;
  line-height: normal;
  padding: 9px 14px 9px;
  -webkit-border-radius: 6px;
  -moz-border-radius: 6px;
  border-radius: 6px;
}
.btn.small {
  padding: 7px 9px 7px;
  font-size: 11px;
}
:root .alert-message, :root .btn {
  border-radius: 0 \0;
}
button.btn::-moz-focus-inner, input[type=submit].btn::-moz-focus-inner {
  padding: 0;
  border: 0;
}


/*----- Prototip styles for shortcut menu -----*/
	
.shortcuts a, .shortcuts a:visited { color: #333; }
.shortcuts a:hover { background: #F3B329; color: #fff; }


/*----- Classes that need PIE -----*/

.banner_logo a, .navshortcut, .navtabs a, .subnavtabs, .content_wrap
{ behavior: url(PIE.php); position: relative; }
