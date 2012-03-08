/* colours
``````````````````````````````
#33A6FF (banner bg, button 100%, secondary_nav a:focus)
#E8E8E8 (background)
#C1E4FF (content header)
#179AFF (button border)
#52B4FF (button 0%)
#63BBFF (secondary_nav a)
#72C1FE (add button)
``````````````````````````````*/

/*----- Blue theme site, uses css.inc.php as basic layout example can be seen in example.css -----*/

body { background: #e8e8e8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
a { color: #484848; }
a:visited { color: #484848; }
a:hover { color: #333; }


/* header and navigation 
``````````````````````````````*/

.banner { background-color: #33A6FF; padding: 1em 0; }
.banner_logo { float: left; margin: 0 1em; }
.banner_logo img { padding: .5em; background-color: #fff;
										  -webkit-border-radius: 5px; 
										     -moz-border-radius: 5px; 
										          border-radius: 5px;
										   -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
										                 
										             -webkit-transition: all 0.3s ease-out;  
										     -moz-transition: all 0.3s ease-out;  
										      -ms-transition: all 0.3s ease-out;  
										       -o-transition: all 0.3s ease-out;  
										          transition: all 0.3s ease-out;}
										             
.banner_logo img:hover { -webkit-box-shadow: 0px 0px 12px #fff; -moz-box-shadow: 0px 0px 12px #fff; box-shadow: 0px 0px 12px #fff;  }					             
										          
.banner_custname { top: 24px; left: 255px; color: #fff; font-size: 1.5em; font-weight: bold; }

.banner_links_wrap { margin: 1.5em 1em 0 1em; color: #fff;  }
.banner_links { margin: 0; padding: 0; list-style-type: none; }	
.banner_links li { border-right: 2px solid #fff; }
.banner_links li a { padding: 0 0.5em; color: #fff; font-weight: bold; text-decoration: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; margin: 0; border: 0; }
.banner_links li.bl_last { border: 0; }
.banner_links li a:hover { text-decoration: underline;  }
.banner_links li a:active {  }
.banner_links li a:focus {  }

.primary_nav { background: #33A6FF; margin-bottom: 2em;}
	
.navtabs { margin: 0 1em; padding: 0; float: left; list-style-type: none;  }
.navtabs li { margin-right: 1em; }

.navtabs a { display: block; padding: .65em 1.2em; font-size: 1.3em; font-weight: bold; color: #fff; text-decoration: none; 
										  -webkit-transition: all 0.3s ease-out;  
										     -moz-transition: all 0.3s ease-out;  
										      -ms-transition: all 0.3s ease-out;  
										       -o-transition: all 0.3s ease-out;  
										          transition: all 0.3s ease-out;	}
		.navtabs a:hover, 
		.navtabs a:active, 
		.navtabs .navtab_active a { background: #e8e8e8; color: #33A6FF !important; 
																		
  -webkit-border-radius: 2px 2px 0 0 ; 
     -moz-border-radius: 2px 2px 0 0 ; 
          border-radius: 2px 2px 0 0 ; 
  -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
																		-webkit-box-shadow: 0px -1px 1px #7b7b7b; 
																	     -moz-box-shadow: 0px -1px 1px #7b7b7b; 
																	          box-shadow: 0px -1px 1px #7b7b7b; }
		
.navtabs a:focus { color: #179aff; } 

.navshortcut { margin: 0 1em; color: #fff; font-weight: bold; border: 1px solid #179AFF; 
-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
		
	  background-color: #51B3FF;
	  background-image: -webkit-gradient(linear, left top, left bottom, from(#51B3FF), to(#33A6FF)); 
	  background-image: -webkit-linear-gradient(top, #51B3FF, #33A6FF); 
	  background-image:    -moz-linear-gradient(top, #51B3FF, #33A6FF); 
	  background-image:     -ms-linear-gradient(top, #51B3FF, #33A6FF); 
	  background-image:      -o-linear-gradient(top, #51B3FF, #33A6FF); 
	  background-image:         linear-gradient(top, #51B3FF, #33A6FF);
	            filter: progid:DXImageTransform.Microsoft.gradient(startColorStr='#51B3FF', EndColorStr='#33A6FF');}
.shortcutmenu { padding: 5px 8px; }

.shortcutmenu img { padding: 0 0 0 5px; }
.nav_shortcuts_menu { display: none; }


.subnavtabs { width: 94%; margin: 0 auto; padding: 0 1em; background: #C1E4FF; 
-webkit-box-shadow: 0px 1px 4px #7b7b7b; -moz-box-shadow: 0px 1px 4px #7b7b7b; box-shadow: 0px 1px 4px #7b7b7b; }
.subnavtabs li { margin-right: .5em; font-size: 11px; }
.subnavtabs a { display: block; margin: 0; padding: 1.8em 1.2em; font-weight: bold; color: #fff; 
											-webkit-transition: color 0.3s ease-out;  
										     -moz-transition: color 0.3s ease-out;  
										      -ms-transition: color 0.3s ease-out;  
										       -o-transition: color 0.3s ease-out;  
										          transition: color 0.3s ease-out;	}	
		
.subnavtabs a:hover,
.subnavtabs a:focus,
.subnavtabs a:link, .subnavtabs a:active, .subnavtabs a:visited		{ color: #179aff; }
.subnavtabs .navtab_active a { background: url('img/nav_arrow.png') no-repeat bottom center; color: #179aff; }

			
/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { width: 94%; margin: 0 auto; margin-bottom: 1em; padding: 0 1em; background: #fff;
-webkit-box-shadow: 0px 2px 4px #7b7b7b; -moz-box-shadow: 0px 2px 4px #7b7b7b; box-shadow: 0px 2px 4px #7b7b7b; }
          
.sectitle { width: 100%; }
.secbutton { width: 23%; }
.sectimeline { width: 76.5%; }

.page_details_wrap { margin: 1em; }

.pagetitle { color: #33a6ff; font-weight: bold; font-size: 1.5em; margin: 1em;}
.pagetitlesubtext { color: #33a6ff; font-weight: normal; font-size: 1em; font-style: normal; }

.big_button_wrap { margin: 2em 0 0 1.5em; }
.newjob, .emrjob { text-indent: -9999px; }
.newjob a, .emrjob a { display: block; width: 196px; height: 60px; margin: 0 0 0.5em 0; }
.newjob a { background: url('../img/themes/blue/easystart.png'); }
.emrjob a { background: url('../img/themes/blue/emergency.png'); }
.newjob a:hover { background: url('../img/themes/blue/easystart_hover.png'); }
.emrjob a:hover { background: url('../img/themes/blue/emergency_hover.png'); }

#t_zoom a, .feedtitle a:hover { color: #179AFF; }
#t_zoom a:hover, .feedtitle a, .feed_content a, .feed_item a { color: #333; }


/*----- Buttons, changes to the basic buttons featured across the site -----*/

.btn { background: #c6c6c6; margin: 0 8px 5px 0; padding: 4px 0; border: none; color: #444; cursor: pointer; border: 1px solid #8a8a8a; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: inset 0px 0px 2px #fff; -moz-box-shadow: inset 0px 0px 2px #fff; box-shadow: inset 0px 0px 2px #fff; }

.btn:hover { background: #dbdbdb;
-webkit-box-shadow: 0px 1px 3px #777; -moz-box-shadow: 0px 1px 3px #777; box-shadow: 0px 1px 3px #777; }

.btn:focus { background: #b2b2b2; color: #333;
-webkit-box-shadow: inset 0px 1px 3px #777; -moz-box-shadow: inset 0px 1px 3px #777; box-shadow: inset 0px 1px 3px #777; }
          

/* +----------------------------------------------------------------+
	 | window                                                         |
   +----------------------------------------------------------------+ */
   
.window {	border: none;
					margin: .5em 0 1em 0;
					padding: .5em;    
					background-color: #fff; }
					        
	.window_title_wrap 	{ padding: 0; position: relative;} 
		.window_title_l, 
		.window_title_r 	{ display: none; } /* hiding from newer browsers, ie specific styles for these follow */
		.window_title 		{ display: block; margin: 0 0 .5em 0; padding: .5em .75em 0 2em; color: <?=$primary?>; font-size: 1.5em; color: #33A6FF;
		 										/*background: url(img/themes/<?=$theme?>/win_t.gif) repeat-x 0 -1px;*/
		 										/*background-color: #E8E8E8;*/ height: 2em;
		 										border-bottom: 1px solid #C1E4FF;
		  									/*-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;  
		 										-webkit-border-radius: 8px 8px 0 0 ; 
			   									 -moz-border-radius: 8px 8px 0 0 ; 
					   									  border-radius: 8px 8px 0 0 ;*/
					   					 }
			
		.window_body_wrap { position: relative; margin: 0; padding: .5% 1%; }
			.window_body_l, .window_body_r { display: none; } /* hide from newer browsers... */
			.window_body { position: relative; }
			table.window_body { /*width: 100%;*/ margin: .5em;}
	
		.window_foot_wrap { display: none; } /* hide from newer browsers... */

.feedfilter a { display: block; padding: 5px 8px; border-bottom: 1px dashed #ccc; }
.feedfilter img { margin: 0 8px 0 0; }

			
/* +----------------------------------------------------------------+
   | start page                                                     |
   +----------------------------------------------------------------+ */
			

.content_row .timeline { /*background-color: #C1E4FF;*/ }


/*----- IE7 classes -----*/

.ie7 div.window_aside { margin: 0 1.8% 0 0; }


/*----- Prototip styles for shortcut menu -----*/
	
.shortcuts a, .shortcuts a:visited { color: #179AFF; }
.shortcuts a:hover { background: #C1E4FF; color: #179AFF; }


/* ==|== PIE classes ======================================== */

.main_content, .banner_logo img, .navtabs a, .navtab_active a, .nav_secondary a, .btn, .nav_shortcuts  
{ behavior: url(PIE.php); position: relative; }
