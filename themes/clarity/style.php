/* CLARITY THEME CSS */


/* colours 
``````````````````````````````
nav bar bg glass style fade

top top - #EAE9E9
top btm - #E1E0E0
btm top - #D9D9D9
btm btm - #C4C2C2
----------------

big send message button 

top top - #5581CD
top btm - #406EC0
btm top - #295AB2
btm btm - #295AB2
----------------

content header color - #105BA4 
light header color - #4D84BA
a color - #105BA4

light text color - #999
body text color - #444

``````````````````````````````*/

/* basics
``````````````````````````````*/

body { background: #fff; }
body, table, form, select, input { font-family: 'Helvetica Neue', Helvetica, arial, sans-serif; }
a { color: #105ba4; }
a:hover { color: #4d84ba; }


/*----- Banner, adds in the grey background and moves the client name to sit next to the logo image -----*/

.banner { background: #fff; padding: 10px 25px; margin-bottom: 3px; /*border-bottom: 4px solid #aaa;*/ -webkit-box-shadow: 0px 2px 4px #999; 
     -moz-box-shadow: 0px 2px 4px #999; 
          box-shadow: 0px 2px 4px #999; }
.banner_logo a { background: #fff; display: block; padding: 0 20px 0 0; border-right: 1px solid #ccc; }

.banner_links { padding: 10px 0 0; }		
.banner_links > li { border-right: 1px solid #ccc; }							             
.banner_links li.bl_last { border: none; }
.banner_links li.bl_left, .banner_links li.bl_right { padding: 0; border: 0; }
.banner_links li a { display: block; margin: 0 0 0 8px; padding-right: 8px; color: #105BA4; font: 12px/15px 'Helvetica Neue', Helvetica, arial, sans-serif; }
.banner_links li a:hover { text-decoration: none; color: #105BA4 }
.banner_custname { position: relative; display: inline; padding: 6px 20px 5px 20px; color: <?=$theme2?>; font: 1.58333333em/1.9em baskerville, georgia, serif; }


/*----- Navigation -----*/

.primary_nav { margin: 0; padding: 0; border-top: 1px solid #fafafa;
background: #eae9e9; /* Old browsers */
/* IE9 SVG, needs conditional override of 'filter' to 'none' */
background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2VhZTllOSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iI2UxZTBlMCIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUxJSIgc3RvcC1jb2xvcj0iI2Q5ZDlkOSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNjNGM0YzQiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
background: -moz-linear-gradient(top, #eae9e9 0%, #e1e0e0 50%, #d9d9d9 51%, #c4c4c4 100%); /* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eae9e9), color-stop(50%,#e1e0e0), color-stop(51%,#d9d9d9), color-stop(100%,#c4c4c4)); /* Chrome,Safari4+ */
background: -webkit-linear-gradient(top, #eae9e9 0%,#e1e0e0 50%,#d9d9d9 51%,#c4c4c4 100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top, #eae9e9 0%,#e1e0e0 50%,#d9d9d9 51%,#c4c4c4 100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top, #eae9e9 0%,#e1e0e0 50%,#d9d9d9 51%,#c4c4c4 100%); /* IE10+ */
background: linear-gradient(top, #eae9e9 0%,#e1e0e0 50%,#d9d9d9 51%,#c4c4c4 100%); /* W3C */
/*-webkit-box-shadow: 0px 2px 4px #999; 
     -moz-box-shadow: 0px 2px 4px #999; 
          box-shadow: 0px 2px 4px #999; */
}

.primary_nav li { border-right: 1px solid #ccc;  }
.primary_nav li a { display: block; /* border-left: 1px solid #e1e1e1;*/ padding: 8px 20px 10px 20px; color:<?=$primary?>; text-shadow: 0 1px 1px #fff; font-weight: bold; font-size: 1.25em; }
.primary_nav a:hover, 
.primary_nav a:active {  } 
.primary_nav .navtab_active a { background-color: #fff; /*-webkit-box-shadow: inset 0px 1px 3px #666; -moz-box-shadow: inset 0px 1px 3px #666; box-shadow: inset 0px 1px 3px #666; */}
		
.navshortcut { font-size: 14px; color: <?=$primary?>; margin: 3px 10px 0 0;
	background: #eeeeee;
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2VlZWVlZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjQ5JSIgc3RvcC1jb2xvcj0iI2VlZWVlZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iI2ZhZmFmYSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmYWZhZmEiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #eeeeee 0%, #eeeeee 49%, #fafafa 50%, #fafafa 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(49%,#eeeeee), color-stop(50%,#fafafa), color-stop(100%,#fafafa));
	background: -webkit-linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);
	background: -o-linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);
	background: -ms-linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);
	background: linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);

	-webkit-box-shadow: inset 0px 1px 3px #999; -moz-box-shadow: inset 0px 1px 3px #999; box-shadow: inset 0px 1px 3px #999;
	-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; 
	-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;}


.shortcutmenu {  padding: 4px 7px 4px 11px; cursor: pointer; }
.shortcutmenu img { padding: 2px; } 

.subnavtabs {  width: auto; list-style-type: none; margin: 4px 0; padding: 0; }
  
.subnavtabs li { margin: 0 0 0 1em; padding-top: 4px; font: 11px/11px 'Helvetica Neue',Helvetica,arial,sans-serif; }
.subnavtabs a { padding: 6px 12px 8px 12px; font-weight: bold; color: <?=$theme1?>;	}	
.subnavtabs a:hover { text-decoration: none;  color: <?=$primary?> }	
.subnavtabs a:focus,
.subnavtabs a:active {  }
.subnavtabs .navtab_active a { color: <?=$primary?>; text-decoration: none;
	background: #eeeeee;
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2VlZWVlZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjQ5JSIgc3RvcC1jb2xvcj0iI2VlZWVlZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iI2ZhZmFmYSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmYWZhZmEiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #eeeeee 0%, #eeeeee 49%, #fafafa 50%, #fafafa 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(49%,#eeeeee), color-stop(50%,#fafafa), color-stop(100%,#fafafa));
	background: -webkit-linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);
	background: -o-linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);
	background: -ms-linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);
	background: linear-gradient(top,  #eeeeee 0%,#eeeeee 49%,#fafafa 50%,#fafafa 100%);

	-webkit-box-shadow: inset 0px 1px 3px #999; -moz-box-shadow: inset 0px 1px 3px #999; box-shadow: inset 0px 1px 3px #999;
	-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; 
	-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
																	  

/*----- Content sections, section widths changed to the layout slightly -----*/

.content_wrap { margin: 0 0 20px 0; padding: 15px; 
-webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.sectitle { width: 40%; float: right; text-align: right;}
.secbutton { width: 60%; }
.sectimeline { width: 100%; display: none;}
.secwindow { width: 100%; }

.pagetitle { margin: 0.4em 0; font-family: 'Helvetica Neue',Helvetica,arial,sans-serif; color: <?=$theme2?>; }

.newjob, .emrjob { float: left; display: inline; margin: 0 0 15px 15px; }
.newjob a, .emrjob a { padding: 10px 20px; }
.newjob a, .emrjob a { color: #FFFFFF; font-size: 1.8em; text-shadow: 0 -1px 0 #222; border: 2px solid #ccc;
  background: #5682ce;
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzU2ODJjZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iIzU2ODJjZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUxJSIgc3RvcC1jb2xvcj0iIzI5NWFiMiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiMyOTVhYjIiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #5682ce 0%, #5682ce 50%, #295ab2 51%, #295ab2 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#5682ce), color-stop(50%,#5682ce), color-stop(51%,#295ab2), color-stop(100%,#295ab2));
	background: -webkit-linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	background: -o-linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	background: -ms-linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	background: linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	-webkit-border-radius: 8px; -moz-border-radius: 8px; border-radius: 8px; 
	-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
	-webkit-box-shadow: inset 0px 0px 1px #999; -moz-box-shadow: inset 0px 0px 1px #ffffff; box-shadow: inset 0px 0px 1px #ffffff; }

.newjob a:hover, .emrjob a:hover { background: #295ab2; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window { background: none; border: none; }

.window_title { margin: 0 15px; padding: 10px 0; font: 17px 'Helvetica Neue',Helvetica,arial,sans-serif; color: #333; }

.window_body_wrap { background: #fff; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; border: 1px solid #ccc;
 -webkit-box-shadow: 0px 1px 2px #999; -moz-box-shadow: 0px 1px 2px #999; box-shadow: 0px 1px 2px #999; }

#t_zoom a { color: #484848; }
#t_zoom a:hover { color: #0064CD; }

.feed_btn_wrap { border-bottom: 1px solid #ddd; }

.feedfilter a { display: block; padding: 5px 8px 5px 12px; border-bottom: 1px solid #eee; }
.feedfilter img { margin: 0 8px 0 0; }

#feeditems .content_row, .feed_item { border-bottom: 1px solid #eee; }

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

.btn { cursor: pointer; display: inline-block; background-color: #f1f1f1; padding: 3px; color: #555; font-size: 11px; line-height: 13px; 
	
	background: #f1f1f1;
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2YxZjFmMSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iI2YxZjFmMSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUxJSIgc3RvcC1jb2xvcj0iI2Q1ZDVkNSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNkNWQ1ZDUiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #f1f1f1 0%, #f1f1f1 50%, #d5d5d5 51%, #d5d5d5 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f1f1f1), color-stop(50%,#f1f1f1), color-stop(51%,#d5d5d5), color-stop(100%,#d5d5d5));
	background: -webkit-linear-gradient(top,  #f1f1f1 0%,#f1f1f1 50%,#d5d5d5 51%,#d5d5d5 100%);
	background: -o-linear-gradient(top,  #f1f1f1 0%,#f1f1f1 50%,#d5d5d5 51%,#d5d5d5 100%);
	background: -ms-linear-gradient(top,  #f1f1f1 0%,#f1f1f1 50%,#d5d5d5 51%,#d5d5d5 100%);
	background: linear-gradient(top,  #f1f1f1 0%,#f1f1f1 50%,#d5d5d5 51%,#d5d5d5 100%);

  -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; 
	-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
	
	-webkit-box-shadow: 0px 0px 1px #444; -moz-box-shadow: 0px 0px 1px #444; box-shadow: 0px 0px 1px #444; }

.btn:hover { color: #fff; text-decoration: none; 
  background: #5682ce;
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzU2ODJjZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUwJSIgc3RvcC1jb2xvcj0iIzU2ODJjZSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUxJSIgc3RvcC1jb2xvcj0iIzI5NWFiMiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiMyOTVhYjIiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top,  #5682ce 0%, #5682ce 50%, #295ab2 51%, #295ab2 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#5682ce), color-stop(50%,#5682ce), color-stop(51%,#295ab2), color-stop(100%,#295ab2));
	background: -webkit-linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	background: -o-linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	background: -ms-linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	background: linear-gradient(top,  #5682ce 0%,#5682ce 50%,#295ab2 51%,#295ab2 100%);
	-webkit-box-shadow: inset 0px 0px 3px #444; -moz-box-shadow: inset 0px 0px 3px #444; box-shadow: inset 0px 0px 3px #444; }


/*----- Prototip styles for shortcut menu -----*/
	
.shortcuts a, .shortcuts a:visited { color: #333; }
.shortcuts a:hover { background: #62CFFC; color: #fff; }


/*----- ie7 styles -----*/
.ie7 div.window_aside { margin: 0 1.8% 0 0; }


/*----- Classes that need PIE -----*/

.banner_links li a, .navtabs a, .navshortcut, .newjob a, .emrjob a, .btn
{ behavior: url(PIE.php); position: relative;}
