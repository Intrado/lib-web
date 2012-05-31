<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

?>

.newform_container {

	overflow: visible;
}

/* allows offsetParent to be correct for helper */
form.newform {
	position: relative;
	float: left;
	width: 100%;
}

.formspinner {
	margin-top: 5px; 
	margin-left: 10px;
}

.helpicon {
	margin-left: 10px; 
}

/*---------- Guide helper ----------*/

#jobedit_helpercell,
#emaileedit_helpercell,
#settings_helpercell,
#list_helpercell,
#addressedit_helpercell,
#phonerecord_helpercell,
#facebookmessage_helpercell,
#twittermessage_helpercell,
#feedform_helpercell,
#pagemessage_helpercell,
#phoneadvanced_helpercell,
#webfeatures_helpercell,
#phonesurvey_helpercell,
#messagegroupedit_helpercell,
#start_helpercell,
#messagePhoneText_helpercell,
#messagePhoneTranslate_helpercell,
#messageEmailText_helpercell,
#messageEmailTranslate_helpercell,
#messageSmsText_helpercell,
#facebookauth_helpercell,
#twitterauth_helpercell,
#socialMedia_helpercell,
#scheduleOptions_helpercell,
#scheduleAdvanced_helpercell { width: 200px; padding: 0.5em 0;  }

.helper { width: 180px; margin: -6px 0 0; padding: 0 5px 0 10px; overflow: hidden; display: none; z-index: 99; }
.helper:before { content: ''; position: absolute; top: 12px; left: -10px; display: block; width: 0; height: 0; 
border-color: transparent #ccc transparent transparent; border-width: 10px; border-style: solid; }
.helper:after { content: ''; position: absolute; top: 12px; left: -9px; display: block; width: 0; height: 0; 
border-color: transparent #f0f0f0 transparent transparent; border-width: 10px; border-style: solid; }

.helper .helpercontent { background: #f0f0f0; height: 150px; padding: 0 7px; font-size: 9pt; font-family: sans-serif; overflow: auto; border-right: 1px solid #ccc; border-left: 1px solid #ccc; }
.helper .helpercontent ul { padding-left: 1.2em; margin-left: 0px; }
.helper .helpercontent p { margin: 0 0 4px 0; }
.helper .info { padding-top: 4pt; font-size: 8pt; text-align: center; }

.helper .title { background: #f0f0f0; padding: 3px 0; font-size: 15px; font-weight: bold; text-align: center; border-radius: 5px 5px 0 0;
border-top: 1px solid #ccc; border-right: 1px solid #ccc; border-bottom: none; border-left: 1px solid #ccc;}
.helper .title img { display: block; padding: 2px 3px 0;  }

.helper .toolbar { background: #e4e4e4; height: 20px; padding-bottom: 3px; border: 1px solid #CCCCCC; border-radius: 0 0 5px 5px; }
.helper .toolbar img { padding: 3px 3px 0px 3px; }



/* fieldset is a container for a set of field elements, typ associated with a help page */

.newform h2 {
	margin-top: .5em; 
	padding: 0.3125em 0.625em 0 ;
	/*background: transparent url('../img/header_bg.gif') repeat-x 0 0 ;*/
	/*border-bottom: 1px dotted #A4C8E9;*/
	color: /* #87AFD4 */ #222; font-weight: normal;
	-webkit-border-radius: 10px 10px 0 0; 
     -moz-border-radius: 10px 10px 0 0; 
          border-radius: 10px 10px 0 0; 
  -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
}

/*---------- New forms layout ----------*/

.newform fieldset { background: #f1f1f1; width: 100%; margin-bottom: 1em; border: none; border-radius: 5px; }
 
.formfieldarea { padding: 8px 10px; }
.formfieldarea .underneathmsg { float: left; display: inline; width: 100%; padding: 0 0 0 175px; font-weight: bold; color: #cc0000; line-height: 22px; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }

.newform .formtitle { float: left; display: inline; width: 165px; padding: 4px 0; text-align: right; }
.newform .formtitle .formlabel { font-weight: bold; font-size: 1.1em; line-height: 16px; }
.newform .formtitle .formicon {  }

.newform .formcontrol { margin: 0 0 0 175px; }
.newform .formcontrol h3 { margin: 0 0 15px 0; }
.newform .formcontrol input[type="text"] { width: 250px; padding: 4px 5px; border: 1px solid #E7E7E7; overflow: hidden; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newform .formcontrol input[type="radio"] { margin: 0 5px 5px 0; }
.newform .formcontrol textarea { width: 425px; margin: 0 0 5px 0; padding: 4px 5px; border: 1px solid #E7E7E7; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newform .formcontrol input#account_brandthemecustomize { float: left; display: inline; margin: 0 5px 0 0; }
.newform .formcontrol .social_note { width: 350px; margin: 2px 0; font-size: 1.1em; line-height: 16px; }
.newform .formcontrol .confirm_note { width: 430px; margin: 2px 0; font-size: 1.1em; line-height: 16px; }
.newform .formcontrol .feed_url { margin: 2px 0 10px 0; font-size: 1.1em; line-height: 16px; }

.newform .formcontrol ul { margin: 0; padding: 3px 0 0 15px;  }

.newform .formcontrol .translate { max-height: 150px; overflow: auto; }
.newform .formcontrol .gBranding {}
.newform .formcontrol .gBrandingText { font-size: 11px; }

.newform .formcontrol .radiobox { float: left; display: inline; width: 250px; list-style-type: none; padding: 0; margin: 0 20px 0 0; }
.newform .formcontrol .radiobox li { padding: 2px 0; }
.newform .formcontrol .radiobox input[type="radio"],
.newform .formcontrol .radiobox input[type="checkbox"] { margin: 0 5px 0 0; }
.newform .formcontrol #accessprofile_publish { float: none; display: block; }
.newform .formcontrol #accessprofile_subscribe { float: none; display: block; }

.newform .formcontrol .radio_box { width: 250px; list-style-type: none; padding: 0; margin: 0 20px 0 0; }

.newform .formcontrol select  { margin: 0; padding: 4px 6px; border: 1px solid #E7E7E7; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.newform .formcontrol .datafields input { width: 140px; }

.newform .introwidget select { float: left; display: inline; margin: 0 5px 0 0; }


/* -------- CSS redesign ------- */

.newform .formcontrol iframe { overflow: hidden; }
.newform .formcontrol > label { margin-left: 5px; line-height: 22px;}

.newform fieldset#AddRuleFieldmap { width: 130px; padding: 0; margin: 0; background: none; }
.newform fieldset#AddRuleFieldmap select { width: 120px; padding: 4px; }
.newform fieldset#AddRuleCriteria { width: 100px; padding: 0; margin: 0; background: none; }
.newform fieldset#AddRuleValue { width: 60px; padding: 0; margin: 0; background: none; }
.newform fieldset#AddRuleValue input { width: 50px; }
.newform fieldset#AddRuleAction { width: 75px; padding: 0; margin: 0; background: none; } 

.newform select, .newform input { -webkit-transition: all 0.3s ease-out; -moz-transition: all 0.3s ease-out; -ms-transition: all 0.3s ease-out; -o-transition: all 0.3s ease-out; transition: all 0.3s ease-out; }

.newform .MultiCheckbox { float: left; display: inline; width: 250px; margin: 0; padding: 7px 0 0; list-style-type: none; }
.newform .MultiCheckbox li { height: 25px; }
.newform .MultiCheckbox input[type="checkbox"] { float: left; display: inline; margin: 0 5px 0 0; }
.newform .list th.MultiCheckbox label { float: left; display: inline; margin: 0 10px 0 0; }

.newform .form_list_table { float: left; display: inline; width: 250px; margin: 0; padding: 0; }
.newform .list { margin: 0 0 5px 0; }
.newform .repeatjob { width: 350px; }

#cke_reusableckeditor { float: left; }
  
.newform .vertical .formlabel  {
	text-align: left;
	width: auto;
	margin-left: 2px;
}

.newform .vertical .formicon { float: none; display: inline; }

.newform /*.formcontrol >*/ input[type="text"]:focus, 
.newform /*.formcontrol >*/ select:focus { 
  -webkit-box-shadow: 0px 0px 6px #9696FF; 
     -moz-box-shadow: 0px 0px 6px #9696FF; 
          box-shadow: 0px 0px 6px #9696FF;  } 
        

.newform .formfieldarea { clear: both;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
 -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
 
 
 /* ----------------- rule widget --------------------*/
 
.newform .ruleHelperContentDiv	{ padding: 3px; background-color: #ffcccc; border-radius: 3px;}
.newform .rulesTable 						{ margin-bottom: 10px; }
.newform .ruleWarningDiv 				{ padding: 2px; color: red; }

.newform .multiCheckBox_checkAll { float: left; white-space: nowrap; }
.newform .multiCheckBox_clear		 { float: right; white-space: nowrap; }
.newform .multiCheckBox_selectOptions	{ width:130px; height:1px; clear:both; }


/* ----------------- Form Validation --------------------- */

.form_error { background-color: rgb(255,255,200); 
	-webkit-border-radius: 10px; 
     -moz-border-radius: 10px; 
          border-radius: 10px;
  -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }


/* ----------------------------- */


.newform .radiobox hr {
	border-top: 1px dotted gray;
	border-bottom: none;
}

.newform .msgbody {
	clear: both;
	font-style: normal;
	font-size: 90%;
	overflow: auto;
	border: 1px solid gray;
}

.newform .msgdetails { float: left; display: inline; margin: 0 0 0 5px; border-width: 1px; border-spacing: 2px; border-style: solid; border-color: gray; background-color: white; }
.newform .msginfo {
	clear: both;
	font-style: normal;
	font-size: 90%;
}

.newform .msgattachment {
	clear: both;
	font-style: italic;
	font-size: 90%;
}
.newform .msglabel {
	margin-top: 3px;
	font-size: 110%;
	width: 90px;
	vertical-align: top;
}

.newform .htmlradiobuttonbigcheck, 
.newform .htmlradiobuttonbigcheck div, 
.newform .htmlradiobuttonbigcheck table, 
.newform .htmlradiobuttonbigcheck td { border: none;	padding: 0;	margin: 0; }
.newform .htmlradiobuttonbigcheck button 	{ border: none;	margin: 0; }
	
.newform .htmlradiobuttonbigcheck td 			{ color: black; }
.newform .htmlradiobuttonbigcheck button 	{ background: none; }

.wiznav_disabled {
	color: gray;
}

.wiznav_active img,
.wiznav_enabled img,
.wiznav_disabled img { padding: 2.5px 7px 0 0; vertical-align: top; }

.wiznav_active {
	font-weight: bold;
	background-color: #def;
}

.wiznav_enabled a {
	text-decoration: none;
}
.wiznav_enabled a:hover {
	text-decoration: underline;
}


.wiznavcontainer { padding: 3px; margin: 3px; margin-top: 15px; }

ol.wiznav_0 { padding: 0; margin: 0px;  border: none; list-style: none; }
li.wiznav_0 { padding: 7px 5px; margin: 0px; border-bottom: 1px dashed #ccc; }
ol.wiznav_1 { padding: 0px; margin: 0px; list-style: none; }
li.wiznav_1 { padding: 7px 0 0; margin: 0px; }
ol.wiznav_2 { padding: 0px; margin: 0px; list-style: none; }
li.wiznav_2 { padding: 7px 0 0; margin: 0px; }

li.wizbuttonlist {
	/*list-style-type: circle;
	list-style-image: url(../img/icons/bullet_blue.gif);
	list-style-position: outside;*/
}

/*-------- New job accordion ---------*/

.accordiontitlediv { background-color: #f1f1f1; margin: 0 0 5px 0; padding: 2px 10px; color: #333; background-position: 5px 11px; background-repeat: no-repeat; cursor: pointer; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.accordiontitlediv td.middle { text-align: left; }
.accordiontitlediv td.left, .accordiontitlediv td.right { display: none; }
.accordiontitledivexpanded { background: #f1f1f1 url('../img/arrow_down.gif') 5px 11px no-repeat; }
.accordiontitledivcollapsed { background: #f1f1f1 url('../img/arrow_right.gif') 5px 11px no-repeat; }
.accordiontitledivlocked { color: rgb(180,180,180); }
.accordiontitleicon { margin-left: 5px; margin-right: 5px; vertical-align: middle; }
.accordioncontentdiv { background: #f1f1f1; margin: -10px 0 5px 0; padding: 5px; 
-webkit-border-radius: 0 0 5px 5px; -moz-border-radius: 0 0 5px 5px; border-radius: 0 0 5px 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.accordioncontentdiv ul.exist_list { margin: 0; padding: 0; list-style-type: none; }
.accordioncontentdiv ul.exist_list li { display: block; height: 25px; }
.accordioncontentdiv ul.exist_list li input { margin: 0 5px 0 0; }

.accordioncontentdiv fieldset { width: inherit; }
.accordioncontentdiv .formtitle { width: inherit; }
.accordioncontentdiv .formcontrol { width: inherit; }

table#addMeWindow { width: 100%; }
table#addMeWindow input[type="checkbox"] { margin: 7px 0; }


/* ----- advanced message sender css -------------------------------- */

/* common 
-----------------*/

.controlcontainer { margin-bottom: 10px; white-space: nowrap; }
.controlcontainer .messagearea { height: 205px; width: 100%; }
.controlcontainer .datafields { font-size: 9px; float: left; }
.controlcontainer .datafieldsinsert { font-size: 9px; float: left; margin-top: 8px; }

#pagemessage .controlcontainer { width: 550px; }

.maincontainerleft { float: left; display: inline; margin-right: 10px; width: 438px; }
.maincontainerseperator { float: left; width: 15px; }
.maincontainerright { float: left; display: inline; width: 155px; margin-left: 10px; padding:6px; border: 1px solid #'.$_SESSION['colorscheme']['_brandtheme2'].'; }


/* email messages 
--------------------*/

.email .maincontainerleft {  }
.email .maincontainerseperator { margin-top: 50px; }
.email .maincontainerright {  }

/* phone messages 
-------------------*/

.phone .controlcontainer { width: 200px; }
.phone .controlcontainer .library { padding: 2px; border: 1px dotted gray; }
.phone .controlcontainer .error { white-space: normal; font-style: italic; color: red; }
.phone .controlcontainer .uploadiframe { overflow: hidden; width: 100%; border: 0; margin: 0; padding: 0; height: 2em; }
.phone .controlcontainer input[type="text"] { width: 185px; }

.phone .maincontainerleft {  }
.phone .maincontainerseperator { margin-top: 80px; }
.phone .maincontainerright {  }


/* +----------------------------------------------------------------+
	 | Form styles for UI redesign                                    |
   +----------------------------------------------------------------+ */
		
		.form_table 		{ table-layout: auto; text-align: left; 900px; }
		 .form_table td { vertical-align: top; }
		 		
		.form_fields, 
		.form_guide 	{ display: block; }

			.form_legend 					{ font-weight: bold; font-size: 1.25em; color: #444; margin-bottom: .33em; }	
			.form_section					{ display: block; margin-bottom: 1.5em; width: 90%; } /* for fieldsets */
			
				.form_section input,
				.form_section select 							{ padding: 5px; font-size: 1.2em; }
				.form_section input[type="text"]	{ width: 250px; }
				.form_section select 							{ width: 263px; }
				
				.form_row { display: block; padding-left: 2em; }
				.form_col { display: block; float: left; padding: .5em; }
				.form_col.form_label 	{ width: 10%; text-align: right; padding-top: 5px;}
				.form_col.form_icon 	{ width: 2%; }
					.form_col.form_icon img { float: right; }
				.form_col.form_control 	{ /* width: 35%; */ }
				
					.form_control label { margin-left: .5em; }
				
				.reqd_field 			{ background: rgb(255,255,220); } /* add this to the same element as form_row */
				
				.valid_true 	{ background: rgb(255,255,255); }
				.valid_false 	{ background: rgb(255,200,200;) }
				
	.form_col.custom_btn_image img { padding-top: 20px; }


/*---------- Newui theme form ----------*/

.newui .newform fieldset { background: #fff; margin-bottom: 1em; border: none; }

.newui .formsectionheader { margin: 5px 0 10px 0; padding: 0; font-size: 14px; line-height: 21px; font-weight: bold; color: #444; text-transform: uppercase; border-bottom: 1px dashed #999; }
 
.newui .formfieldarea { padding: 8px 10px; }
.newui .formfieldarea .underneathmsg { padding: 0 0 0 140px; font-size: 14px; font-weight: normal; color: #cc0000; }

.newui .newform .formtitle { float: left; display: inline; width: 140px; margin: 0; padding: 0; }
.newui .newform .formtitle .formlabel { float: none; display: block; padding: 5px 10px 5px 0; font-size: 14px; line-height: 18px; font-weight: normal; text-align: right; }
.newui .newform .formtitle .formicon { display: none; }

.newui .newform .formcontrol { float: left; display: inline; margin: 0; padding: 0; }
.newui .newform .formcontrol input[type="text"] { display: block; width: 313px; padding: 4px 5px; font-size: 14px; line-height: 18px; border: 1px solid #E2E2E2; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newui .newform .formcontrol input[type="radio"] { margin: 0 5px 5px 0; }
.newui .newform .formcontrol input[type="checkbox"] { margin: 5px 0 0; }
.newui .newform .formcontrol textarea { display: block; margin: 0 0 5px 0; padding: 4px 5px; font-size: 14px; line-height: 18px; border: 1px solid #E2E2E2; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newui .newform .formcontrol input#account_brandthemecustomize { float: left; display: inline; margin: 0 5px 0 0; }

.newui .newform .formcontrol .translate { max-height: 150px; overflow: auto; }
.newui .newform .formcontrol .gBranding {}
.newui .newform .formcontrol .gBrandingText { font-size: 11px; }

.newui .newform .formcontrol .radiobox li { padding: 5px 0; }
.newui .newform .formcontrol .radiobox input[type="radio"],
.newui .newform .formcontrol .radiobox input[type="checkbox"] { margin: 0 5px 0 0; }

.newui .newform .formcontrol select { margin: 0; padding: 4px 6px; border: 1px solid #E7E7E7; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.newui .newform .formcontrol .datafields input { width: 140px; }


/*---------- Message sender form ----------*/

.msg_content_nav li.notactive { display: none; }
.hidden { display: none;}

.window_panel {  }
.window_panel fieldset { padding: 0 0 15px 0; }
.window_panel fieldset.check { padding: 15px 0; }
/*.window_panel fieldset.checklast { margin: 0; padding: 6px 0 16px 0px; }*/
.window_panel input[type="text"],
.window_panel select,
.window_panel textarea { display: inline-block; width: 300px; padding: 5px; font-size: 14px; line-height: 18px; border: 1px solid #ccc; border-radius: 5px; }
.window_panel input[type="text"]:focus,
.window_panel select:focus,
.window_panel textarea:focus { border: 1px solid #58acef; outline: 0px; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6); }
.window_panel label { float: left; display: inline; width: 140px; margin: 0 10px 0 0; padding: 6px 0; font-size: 14px; line-height: 19px; text-align: right; }
.window_panel input.small { float: left; width: 135px; }
.window_panel textarea { /*max-width: 300px; min-width: 300px;*/ width: 96%; min-height: 100px; }
.window_panel select { font-size: 14px; padding: 5px; }
.window_panel input[type="checkbox"] { margin: 9px 5px 0 0; }
.window_panel .addme { float: left; display: inline; text-align: left; width: inherit; }
.window_panel p { margin: 0; padding: 4px 0; color: #888; }
.window_panel .controls { margin: 0 0 0 150px; }
.window_panel .form_actions { background: #ededed; margin: 0; padding: 15px; border-radius: 0 0 8px 8px; border-top: 1px solid #ccc; }

.window_panel iframe { height: 3em; padding-top: 3px; }

.characters {
	float: right;
	margin: 0 4% 0 0;
	padding: 5px 0 0 0;
}

.window_panel input.ok, .window_panel textarea.ok {
	border: 1px solid rgb(75,149,35);
}
.window_panel input.ok[type="text"]:focus, .window_panel textarea.ok:focus { 
	border: 1px solid rgb(75,149,35);
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(75,149,35, 0.6);
}

.window_panel input.er, .window_panel textarea.er {
	border: 1px solid rgb(219,30,30);
}
.window_panel input.er[type="text"]:focus, .window_panel textarea.er:focus { 
	border: 1px solid rgb(219,30,30);
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(219,30,30, 0.6);
}


span.error {
	color: rgb(219,30,30);
	font-size: 0.9em;
}

/*---------- Media queries for form elements ----------*/

@media only screen and (max-width: 1024px) {
	.formcontrol .messagegrid { width: 520px; }
	.maincontainerleft { width: 320px; }
	.newform .formcontrol textarea { width: 300px; }
	.newform .htmlradiobuttonbigcheck { width: 320px; }
	.newform .htmlradiobuttonbigcheck .creation_method { margin: 0 10px 5px 0; }
	
	#jobedit_helpercell,
	#emaileedit_helpercell,
	#settings_helpercell,
	#list_helpercell,
	#addressedit_helpercell,
	#phonerecord_helpercell,
	#facebookmessage_helpercell,
	#twittermessage_helpercell,
	#feedform_helpercell,
	#pagemessage_helpercell,
	#phoneadvanced_helpercell,
	#webfeatures_helpercell,
	#phonesurvey_helpercell,
	#messagegroupedit_helpercell,
	#start_helpercell,
	#messagePhoneText_helpercell,
	#messagePhoneTranslate_helpercell,
	#messageEmailText_helpercell,
	#messageEmailTranslate_helpercell,
	#messageSmsText_helpercell,
	#facebookauth_helpercell,
	#twitterauth_helpercell,
	#socialMedia_helpercell,
	#scheduleOptions_helpercell,
	#scheduleAdvanced_helpercell { width: 175px; }
	.helper { width: 155px; }
}

@media only screen and (max-width: 800px) {
	.newform .formtitle { float: none; display: block; width: inherit; text-align: left; }
	.newform .formcontrol { float: none; display: block; margin: 0; }
	
	.newform .form_list_table { width: 195px; }
	.formcontrol .messagegrid { width: inherit; }
	.formcontrol .messagegrid th { font-size: 11px; }
	.formcontrol .messagegrid td { padding: 2px 0; }
	.maincontainerleft { width: 290px; }
	.maincontainerright { padding: 0; }
	.newform .formcontrol textarea { width: 280px; margin: 0; }
	.controlcontainer .datafields { float: none; }
	.helper { width: 150px; }
}
			
=======
<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

?>

.newform_container {

	overflow: visible;
}

/* allows offsetParent to be correct for helper */
form.newform {
	position: relative;
	float: left;
	width: 100%;
}

.formspinner {
	margin-top: 5px; 
	margin-left: 10px;
}

.helpicon {
	margin-left: 10px; 
}

/*---------- Guide helper ----------*/

#jobedit_helpercell,
#emaileedit_helpercell,
#settings_helpercell,
#list_helpercell,
#addressedit_helpercell,
#phonerecord_helpercell,
#facebookmessage_helpercell,
#twittermessage_helpercell,
#feedform_helpercell,
#pagemessage_helpercell,
#phoneadvanced_helpercell,
#webfeatures_helpercell,
#phonesurvey_helpercell,
#messagegroupedit_helpercell,
#start_helpercell,
#messagePhoneText_helpercell,
#messagePhoneTranslate_helpercell,
#messageEmailText_helpercell,
#messageEmailTranslate_helpercell,
#messageSmsText_helpercell,
#facebookauth_helpercell,
#twitterauth_helpercell,
#socialMedia_helpercell,
#scheduleOptions_helpercell,
#scheduleAdvanced_helpercell { width: 200px; padding: 0.5em 0;  }

.helper { width: 180px; margin: -6px 0 0; padding: 0 5px 0 10px; overflow: hidden; display: none; z-index: 99; }
.helper:before { content: ''; position: absolute; top: 12px; left: -10px; display: block; width: 0; height: 0; 
border-color: transparent #ccc transparent transparent; border-width: 10px; border-style: solid; }
.helper:after { content: ''; position: absolute; top: 12px; left: -9px; display: block; width: 0; height: 0; 
border-color: transparent #f0f0f0 transparent transparent; border-width: 10px; border-style: solid; }

.helper .helpercontent { background: #f0f0f0; height: 150px; padding: 0 7px; font-size: 9pt; font-family: sans-serif; overflow: auto; border-right: 1px solid #ccc; border-left: 1px solid #ccc; }
.helper .helpercontent ul { padding-left: 1.2em; margin-left: 0px; }
.helper .helpercontent p { margin: 0 0 4px 0; }
.helper .info { padding-top: 4pt; font-size: 8pt; text-align: center; }

.helper .title { background: #f0f0f0; padding: 3px 0; font-size: 15px; font-weight: bold; text-align: center; border-radius: 5px 5px 0 0;
border-top: 1px solid #ccc; border-right: 1px solid #ccc; border-bottom: none; border-left: 1px solid #ccc;}
.helper .title img { display: block; padding: 2px 3px 0;  }

.helper .toolbar { background: #e4e4e4; height: 20px; padding-bottom: 3px; border: 1px solid #CCCCCC; border-radius: 0 0 5px 5px; }
.helper .toolbar img { padding: 3px 3px 0px 3px; }



/* fieldset is a container for a set of field elements, typ associated with a help page */

.newform h2 {
	margin-top: .5em; 
	padding: 0.3125em 0.625em 0 ;
	/*background: transparent url('../img/header_bg.gif') repeat-x 0 0 ;*/
	/*border-bottom: 1px dotted #A4C8E9;*/
	color: /* #87AFD4 */ #222; font-weight: normal;
	-webkit-border-radius: 10px 10px 0 0; 
     -moz-border-radius: 10px 10px 0 0; 
          border-radius: 10px 10px 0 0; 
  -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box;
}

/*---------- New forms layout ----------*/

.newform fieldset { background: #f1f1f1; width: 100%; margin-bottom: 1em; border: none; border-radius: 5px; }
 
.formfieldarea { padding: 8px 10px; }
.formfieldarea .underneathmsg { float: left; display: inline; width: 100%; padding: 0 0 0 175px; font-weight: bold; color: #cc0000; line-height: 22px; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }

