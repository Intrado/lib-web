function messagegroupHandleBeforeTabLoad (event, state) {
	if ($$('.HTMLEditorAjaxLoader').length > 0) {
		alert('Please wait until the HTML Editor has loaded.');
		event.stop();
	}
	
	var nexttab = event.memo.nexttab;
	var nexttabpieces = nexttab.split('-');
	if (nexttabpieces.length == 2 && nexttabpieces[0] == 'email') {
		// If the user is tabbing between subtypes, make sure the language stays consistent.
		event.memo.specificsections = [event.memo.nexttab + '-' + state.currentlanguagecode];
	} else if (nexttab == 'emailheaders') {
		event.memo.specificsections = ['emailheaders', 'email-html', 'email-html-' + state.defaultlanguagecode];
	} else if (nexttab == 'phone-voice') {
		event.memo.specificsections = ['phone-voice', 'phone-voice-' + state.defaultlanguagecode];
	}
}

function messagegroupHandleTabLoaded (event, state, existingmessagegroupid, autotranslatorUpdator, readonly) {
	Tips.hideAll();
	
	// NOTE: Message tab icons are the only ones with an ID attribute.
	var messagetabicon = event.memo.widget.sections[event.memo.tabloaded].titleDiv.down('img');
	
	if (messagetabicon.id == 'summaryicon')
		messagetabicon.src = 'img/icons/application_view_columns.gif';
	else if (!readonly && messagetabicon.id.include('autotranslatoricon'))
		messagetabicon.src = 'img/icons/world.gif';
	else if (messagetabicon.id)
		messagetabicon.src = 'img/icons/diagona/16/160.gif';

	messagegroupStyleLayouts(readonly);
	
	var memo = event.memo;
	var languagesectionpieces = memo.specificsections ? memo.specificsections[0].split('-') : null;
	var previoustabpieces = memo.previoustab.split('-');
	var tabloadedpieces = memo.tabloaded.split('-');

	// Keep track of the current destination type, subtype, and languagecode.
	if (tabloadedpieces.length == 3) {
		state.currentdestinationtype = tabloadedpieces[0];
		state.currentsubtype = tabloadedpieces[1];
		state.currentlanguagecode = tabloadedpieces[2];
	} else if (tabloadedpieces.length == 1 || (tabloadedpieces.length == 2 && tabloadedpieces[0] == 'phone')) {
		state.currentdestinationtype = tabloadedpieces[0] != 'summary' ? tabloadedpieces[0] : '';
		state.currentsubtype = tabloadedpieces.length == 2 ? tabloadedpieces[1] : '';
		if (state.currentdestinationtype == 'emailheaders') {
			state.currentdestinationtype = 'email';
			state.currentsubtype = 'html';
		}

		if (languagesectionpieces && languagesectionpieces.length == 3)
			state.currentlanguagecode = languagesectionpieces[2];
		else
			state.currentlanguagecode = state.defaultlanguagecode;
	} else if (tabloadedpieces.length == 2 && tabloadedpieces[0] == 'email') {
		state.currentdestinationtype = 'email';
		state.currentsubtype = tabloadedpieces[1];
	}

	// Event Handlers for specific tabs like summary, autotranslator, individual languages.
	if (memo.tabloaded == 'summary') {
		memo.widget.container.observe('click', function(event, widget, state) {
			var element = event.element();
			if (element.match('img.StatusIcon')) {
				event.stop();
				
				var pieces = element.identify().split('-');
				
				var specificsections = [
					pieces[0] + '-' + pieces[1] + '-' + pieces[2]
				];
				if (pieces[0] != 'sms')
					specificsections.push(pieces[0] + '-' + pieces[1]);
				if (pieces[0] == 'email') {
					specificsections.push('emailheaders');
				}

				var nexttab;
				if (pieces[0] == 'sms') {
					nexttab = pieces[0] + '-' + pieces[1] + '-' + pieces[2];
				} else if (pieces[0] == 'email') {
						nexttab = 'emailheaders'
				} else { // phone
					// NOTE: If is just a single phone language, there will not be a 'phone-voice' tab, it will be named 'phone-voice-en'.
					nexttab = state.countphonelanguages > 1 ? pieces[0] + '-' + pieces[1] : pieces[0] + '-' + pieces[1] + '-' + pieces[2];
				}

				form_load_tab(this, widget, nexttab, specificsections, readonly);
			}
		}.bindAsEventListener(memo.form, memo.widget, state));
	} else if (autotranslatorUpdator) {
		if ((tabloadedpieces.length == 2 && tabloadedpieces[0] == 'email' && state.currentlanguagecode == 'autotranslator' ) || (tabloadedpieces.length == 3 && tabloadedpieces[2] == 'autotranslator')) {
			var autotranslatorbutton =  $('autotranslatorrefreshtranslationbutton');
			if (autotranslatorbutton)
				autotranslatorUpdator(autotranslatorbutton, state);
		}
	}
	
	// Update the status icons on tabs.
	cachedAjaxGet('ajax.php?type=messagegroupsummary&messagegroupid='+existingmessagegroupid,
		function(transport) {
			var previoustabpieces = memo.previoustab.split('-');
			var tabloadedpieces = memo.tabloaded.split('-');

			var results = transport.responseJSON;
			if (!results) {
				state.messagegroupsummary = [];
				return;
			}
			
			state.messagegroupsummary = results.summary;
			state.defaultlanguagecode = results.defaultlanguagecode;
			var tabstatus = false;

			for (var i = 0, count = state.messagegroupsummary.length; i < count; i++) {
				var result = state.messagegroupsummary[i];

				var updateprevioustab = (result.type == 'email' && memo.previoustab == 'emailheaders') || result.type == previoustabpieces[0];
				var updatetabloaded = (result.type == 'email' && memo.tabloaded == 'emailheaders') || result.type == tabloadedpieces[0];

				if(result.type == previoustabpieces[0])
					tabstatus = true;

				if (updateprevioustab || updatetabloaded) {
					if (result.type == 'email' && $('emailheadersicon')) {
						$('emailheadersicon').src = "img/icons/accept.gif";
					}

					if ($(result.type + '-' + result.subtype + 'icon')) {
						$(result.type + '-' + result.subtype + 'icon').src = "img/icons/accept.gif";
					}

					if ($(result.type + '-' + result.subtype + '-' + result.languagecode + 'icon')) {
						$(result.type + '-' + result.subtype + '-' + result.languagecode + 'icon').src = "img/icons/accept.gif";
					}
				}
			}
			if ($(memo.previoustab + 'icon')) {
				$(memo.previoustab + 'icon').src = tabstatus?"img/icons/accept.gif":"img/icons/diagona/16/160.gif";
			}
		}.bindAsEventListener(this, memo, state),
		null,
		readonly || false
	);
}

function messagegroupStyleLayouts(readonly) {
	if (!readonly) {
		var verticaltabs = $$('div.verticaltabstitlediv');
		if (verticaltabs.length > 0)
			verticaltabs[0].setStyle({'marginBottom':'20px'});
	}
}