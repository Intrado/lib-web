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
#scheduleAdvanced_helpercell { padding: 0.5em 0;  }

.helper { width: 150px; margin: -6px 0 0; padding: 0 5px 0 10px; overflow: hidden; display: none; z-index: 99; }
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

.newform h2 { margin: 0 0 5px 0; color: #222; font-size: 18px; }

/*---------- New forms layout ----------*/

.newform fieldset { background: #f1f1f1; margin-bottom: 1em; border: none; border-radius: 5px; }
.newform label { font-size: 13px; line-height: 21px; }

.formfieldarea { padding: 8px; }
.formfieldarea .underneathmsg { float: left; display: inline; width: 100%; padding: 0 0 0 150px; font-weight: bold; color: #cc0000; line-height: 22px; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }

.newform .formtitle { position: relative; float: left; display: inline; width: 120px; padding: 3px 20px 0 0; text-align: right; }
.newform .formtitle .formlabel { font-size: 13px; line-height: 21px; }
.newform .formtitle .formicon { position: absolute; top: 6px; right: 0; }

.newform .formcontrol { margin: 0 0 0 150px; font-size: 13px; line-height: 21px; }
.newform .formcontrol h3 { margin: 0 0 15px 0; }
.newform .formcontrol input[type="text"] { width: 250px; padding: 4px 5px; font-size: 13px; height: 17px; border: 1px solid #E7E7E7; overflow: hidden; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
/*.newform .formcontrol input[type="password"] { width: 250px; padding: 4px 5px; font-size: 13px; line-height: 21px; border: 1px solid #E7E7E7; overflow: hidden; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }*/
.newform .formcontrol input[type="radio"] { margin: 0 5px 5px 0; }
.newform .formcontrol textarea { width: 425px; margin: 0 0 5px 0; padding: 4px 5px; border: 1px solid #E7E7E7; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newform .formcontrol input#account_brandthemecustomize { float: left; display: inline; }
.newform .formcontrol .feed_url { margin: 2px 0 10px 0; font-size: 1.1em; line-height: 16px; }

.newform .formcontrol .social_note,
.newform .formcontrol .confirm_note,
.newform .formcontrol .translate_text { padding: 3px 0 0; font-size: 13px; line-height: 21px; }

/*.newform .formcontrol ul { margin: 0; padding: 0;  }*/

.newform .formcontrol .translate { max-height: 150px; overflow: auto; }
.newform .formcontrol .gBranding {}
.newform .formcontrol .gBrandingText { font-size: 11px; }

.newform .formcontrol .radiobox { float: left; display: inline; width: 250px; list-style-type: none; padding: 3px 0 0; margin: 0 20px 0 0; }
.newform .formcontrol .radiobox li { font-size: 13px; line-height: 21px; }
.newform .formcontrol .radiobox input[type="radio"],
.newform .formcontrol .radiobox input[type="checkbox"] { margin: 0 5px 0 0; }

.newform .formcontrol .multicheckbox { float: left; display: inline; width: 250px; padding: 1px 0 0; margin: 0 20px 0 0; }

.newform .formcontrol #accessprofile_publish,
.newform .formcontrol #accessprofile_subscribe,
.newform .formcontrol #accessprofile_datafields,
.newform .formcontrol #reportcallssearch_organizationids { float: none; display: block; margin-left: 1em; }
.newform .formcontrol #listsearch_quickaddsearch { width: 330px; }

.newform .formcontrol .radio_box { width: 250px; list-style-type: none; padding: 0; margin: 0 20px 0 0; }

.newform .formcontrol select  { margin: 0; padding: 4px 6px; font-size: 13px; border: 1px solid #E7E7E7; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;  -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.newform .formcontrol .datafields input { width: 140px; }

.newform .introwidget select { float: left; display: inline; margin: 0 5px 0 0; }

.newform .signup_settings label { display: inline-block; width: 160px; }
.newform .signup_settings input { margin: 0 5px 5px 0; }
.newform .searchoption { padding: 6px 0 0; }

/* -------- CSS redesign ------- */

.newform .formcontrol iframe { height: 2em; }
.newform .formcontrol > label { margin-left: 5px; line-height: 22px;}

.newform fieldset#AddRuleFieldmap { width: 130px; padding: 0; margin: 0; background: none; }
.newform fieldset#AddRuleFieldmap select { width: 120px; padding: 4px; }
.newform fieldset#AddRuleCriteria { /*width: 210px;*/ padding: 0; margin: 0; background: none; }
.newform fieldset#AddRuleValue { /*width: 60px;*/ padding: 0; margin: 0; background: none; }
.newform fieldset#AddRuleValue input[type="text"] { width: 50px; }
.newform fieldset#AddRuleValue .MultiCheckbox { margin: 0; padding: 0; }
.newform fieldset#AddRuleAction { width: 75px; padding: 0; margin: 0; background: none; } 

.newform select, .newform input { -webkit-transition: all 0.3s ease-out; -moz-transition: all 0.3s ease-out; -ms-transition: all 0.3s ease-out; -o-transition: all 0.3s ease-out; transition: all 0.3s ease-out; }

.newform .MultiCheckbox { /*float: left; display: inline;*/ width: 250px; margin: 5px 0 0 0; padding: 7px 0 0; overflow-y:auto; 
position: relative;}
.newform .MultiCheckbox li { /*height: 25px;*/ }
.newform .MultiCheckbox input[type="checkbox"] { f/*loat: left;*/ /* display: inline;*/ margin: 0 5px 0 0; }
.newform .list th.MultiCheckbox label { float: left; display: inline; margin: 0 10px 0 0; }

.newform .form_list_table { width: 250px; margin: 0; padding: 0; }
.newform .list { margin: 0 0 5px 0; }
.newform .repeatjob { width: 350px; }

#cke_reusableckeditor { float: left; }
  
.newform .vertical .formlabel  {  }
.newform .vertical .formicon {  }

.newform /*.formcontrol >*/ input[type="text"]:focus, 
.newform /*.formcontrol >*/ select:focus { 
  -webkit-box-shadow: 0px 0px 6px #9696FF; 
     -moz-box-shadow: 0px 0px 6px #9696FF; 
          box-shadow: 0px 0px 6px #9696FF;  } 
        

.newform .formfieldarea { clear: both;
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
 -moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
 
 
 /* ----------------- rule widget --------------------*/

 #ruleWidgetContainer table {
 	border-collapse: separate;
 }

#ruleWidgetContainer td.list {
	border: 1px solid rgb(223, 150, 16);
	display: inline;
	float:left;
	margin: 4px;
	padding: 4px;
}

 
.newform .ruleHelperContentDiv	{ padding: 3px; background-color: #ffcccc; border-radius: 3px;}
.newform .rulesTable 						{ margin-bottom: 10px; }
.newform .ruleWarningDiv 				{ padding: 2px; color: red; }

.newform .multiCheckBox_checkAll { float: left; white-space: nowrap; }
.newform .multiCheckBox_clear		 { float: right; white-space: nowrap; }
.newform .multiCheckBox_selectOptions	{ width:130px; height:1px; clear:both; }

#list_newrule_fieldarea > .formtitle  {
	text-align: left;
}
#list_newrule_fieldarea > .formcontrol, #list_advancedtools_fieldarea > .formcontrol  {
	margin: 0;
	clear: left;
	width: 100%;
}


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

ol.wiznav_0 { padding: 0; margin: 0px; border: none; list-style: none; }
li.wiznav_0 { padding: 7px 5px; margin: 0px; border-bottom: 1px dashed #ccc; }
ol.wiznav_1 { padding: 0 0 0 10px; margin: 0px; list-style: none; }
li.wiznav_1 { padding: 7px 0 0; margin: 0px; }
ol.wiznav_2 { padding: 0 0 0 10px; margin: 0px; list-style: none; }
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
.controlcontainer .datafields { float: left; display: inline; font-size: 9px; }
.controlcontainer .datafieldsinsert { float: left; display: inline; font-size: 9px; margin-top: 8px; }

#pagemessage .controlcontainer { width: 550px; }

.maincontainerleft { float: left; display: inline; margin-right: 10px; width: /*438px;*/ }
.maincontainerseperator { float: left; width: 15px; }
.maincontainerright { float: left; display: inline; /*margin-left: 10px;*/ padding:6px; border: 1px solid #'.$_SESSION['colorscheme']['_brandtheme2'].'; }


/* email messages 
--------------------*/

.email .maincontainerleft {  }
.email .maincontainerseperator { margin-top: 50px; }
.email .maincontainerright {  }

#emaileedit_helpsection_5 .formtitle {  }
#emaileedit_helpsection_5 .formtitle .formlabel {  }
#emaileedit_helpsection_5 .formcontrol { /*width: 600px;*/ }
#emaileedit_helpsection_5 .formcontrol .maincontainerleft { width: 65%; }
#emaileedit_helpsection_5 .formcontrol .maincontainerright { width: 175px }
#emaileedit_helpsection_5 .formcontrol .controlcontainer { width: /*600px;*/ }
#emaileedit_helpsection_5 .maincontainerseperator { margin: 0; display: none; }

.formcontrol .datafields { margin: 0 5px 0 0; }
.formcontrol .datafields select { padding: 3px 6px; }
.formcontrol .datafieldsinsert { margin: 20px 0 0; }
.formcontrol .datafieldsinsert button { padding: 3px 10px; }


/* phone messages 
-------------------*/

.phone .controlcontainer {  }
.phone .controlcontainer .library { padding: 2px; border: 1px dotted gray; }
.phone .controlcontainer .error { white-space: normal; font-style: italic; color: red; }
.phone .controlcontainer iframe { width: 100%; border: 0; margin: 0; padding: 0; height: 2em; }
.phone .controlcontainer input[type="text"] {  }

.phone .maincontainerleft { margin: 0; }
.phone .maincontainerseperator { display: none; margin-top: 80px; }
.phone .maincontainerright { margin: 0; }


/* +----------------------------------------------------------------+
	 | Form styles for UI redesign                                    |
   +----------------------------------------------------------------+ */
		
		.form_table 		{ table-layout: auto; text-align: left; 900px; }
		 .form_table td { vertical-align: top; }
		 .form_table td#emailText_helpercell { width: 210px; }
		 		
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

.newui .newform .formtitle { width: 140px; padding: 5px 20px 0 0; }
.newui .newform .formtitle .formlabel { float: none; /*display: block;*/ padding: 5px 0px 5px 0; font-size: 14px; line-height: 18px; font-weight: normal; text-align: right; }
.newui .newform .formtitle .formicon { /*display: none;*/ }

.newui .newform .formcontrol { padding: 0; }
.newui .newform .formcontrol input[type="text"] { width: 313px; height: 28px; padding: 4px 5px; font-size: 14px; border: 1px solid #E2E2E2; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newui .newform .formcontrol input[type="radio"] { margin: 0 5px 5px 0; }
.newui .newform .formcontrol input[type="checkbox"] {  }
.newui .newform .formcontrol textarea { display: block; width: 375px; margin: 0 0 5px 0; padding: 4px 5px; font-size: 14px; line-height: 18px; border: 1px solid #E2E2E2; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.newui .newform .formcontrol input#account_brandthemecustomize { margin: 5px 5px 0 0; }

.newui .newform .formcontrol .domain { padding: 5px 0 0; font-size: 14px; line-height: 18px; }

.newui .newform .formcontrol .translate { max-height: 150px; padding: 5px 0 0; font-size: 14px; line-height: 18px; overflow: auto; }
.newui .newform .formcontrol .translate p { margin: 0; line-height: 18px; }

.retranslateitems .message { font-size: 14px; line-height: 18px; }
.retranslateitems .message p { margin: 0; line-height: 18px; }

.newui .newform .formcontrol .gBranding {}
.newui .newform .formcontrol .gBrandingText { font-size: 11px; }

.newui .newform .formcontrol .radiobox li { padding: 2px 0; }
.newui .newform .formcontrol .radiobox input[type="radio"],
.newui .newform .formcontrol .radiobox input[type="checkbox"] { margin: 0 5px 0 0; }

.newui .newform .formcontrol select { margin: 0; padding: 4px 6px; border: 1px solid #E7E7E7; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.newui .newform .formcontrol .datafields input { width: 140px; }

.newui .controlcontainer {  }

.newui .maincontainerleft { margin: 0; }
.newui .maincontainerseperator { display: none; }
.newui .maincontainerright { margin: 0; padding: 0; }

#phoneadvanced_message-voicerecorder_callcontrol input.easycallphoneinput {  }
#phoneadvanced_message-voicerecorder_callcontrol .btn {}

.newui #emaileedit_helpsection_5 .formcontrol .maincontainerleft,
.newui #emaileedit_helpsection_5 .formcontrol .maincontainerright,
.newui #emaileedit_helpsection_5 .formcontrol .controlcontainer { width: 600px; }

.newui #emailText_helpsection_5 .formcontrol .maincontainerleft,
.newui #emailText_helpsection_5 .formcontrol .maincontainerright,
.newui #emailText_helpsection_5 .formcontrol .controlcontainer { width: 455px; }

.newui .formcontrol .datafields { margin: 0 10px 0 0; }
.newui .formcontrol .datafields select { width: 175px; padding: 3px 6px; font-size: 14px; }
.newui .formcontrol .datafieldsinsert { margin: 25px 0 0; }
.newui .formcontrol .datafieldsinsert button { padding: 3px 10px; }

.newui #list_helpsection_3 .formtitle { width: 100%; text-align: left; }
.newui #list_helpsection_3 .formcontrol { width: 600px; margin: 0; }


/*---------- IE7 fixes ----------*/

.ie7 .newform .formtitle { margin: 0 10px 0 0; }
.ie7 .newform .formcontrol { margin: 0; }


/*---------- Media queries for form elements ----------*/

@media only screen and (max-width: 1024px) {
	.formcontrol .messagegrid { width: 520px; }
	
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
	#scheduleAdvanced_helpercell {  }
	.helper {  }
}

@media only screen and (max-width: 800px) {
	.newform .formtitle { float: none; display: block; width: inherit; text-align: left; }
	.newform .formcontrol { float: none; display: block; margin: 0; }
	.formfieldarea .underneathmsg { float: none; display: block; padding: 0px; }

	.newform .form_list_table { width: 195px; }
	.formcontrol .messagegrid { width: inherit; }
	.formcontrol .messagegrid th { font-size: 11px; }
	.formcontrol .messagegrid td { padding: 2px 0; }
	.maincontainerleft { width: 290px; }
	.maincontainerright { padding: 0; }
	.newform .formcontrol textarea { width: 280px; margin: 0; }
	.controlcontainer .datafields { float: none; }
	.helper { width: 150px; }
	
	.newui .newform .formtitle,
	.newui .newform .formcontrol { float: none; display: block; }
	.newui .newform .formtitle .formlabel { padding: 0 10px 5px 0; text-align: left; }
	.newui .formfieldarea .underneathmsg { font-size: 13px; padding: 0; }
}
