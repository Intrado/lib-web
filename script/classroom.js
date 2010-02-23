
	// Color variables
	var c_hover = "#bbcccc";
	var c_selected = "#C4CCC4";//"#ffcccc";
	var c_none = "#ffffff";
	var h_image = "img/icons/fugue/arrow.gif";

	/*
	 Checked cache contains
	 Checkedcache -> Category -> Contact -> Message

	 ie. Checkedcache -> "Positive" -> "p_0001" -> "m_0001"

	 Checkedcache has an extra level Category to easaly determine if a contact has one or more messages checked in under one category
	*/
	var checkedcache = new Hash();			// History of Contact to Message links

	var checkedcontacts = new Hash();		// List of the Contacts that are currently checked
	var checkedmessages = new Hash();		// List of Messages that are currently checked
	var highlightedmessages = new Hash();	// List of Messages that are currently highlighted
	var highlightedcontacts = new Hash();	// List of Contacts that are currently highlighted

	var markedcontacts = new Hash();
	var markedcomment = false;
	var revealmessages = true;			// Boolean to reveal messages on first click

	var tabs;
	var clock;

	function getstatesrc(state) {
		switch (state) {
			case 0:
				return "img/checkbox-clear.png";
			case 1:
				return "img/checkbox-dash.png";
			case 2:
				return "img/checkbox-check.png";
		}
		return "";
	}

	function clearcache() {
		checkedcache = new Hash()
		checkedcontacts = new Hash();
	}

	function clearchecked() {
		checkedmessages = new Hash();
	}

	function setcache(cache) {
		cache.each(function(event) {
			var people = checkedcache;//.get(event[0]);
			var contactid = event[1];

			if(people.get(contactid) == undefined)
				people.set(contactid,new Hash());
			var contactlink = people.get(contactid);

			// set category icon
			var img = $('c-' + contactid + '-' + event[0]);
			if(img != undefined) {
				img.src = categoryinfo.get(event[0]).img;
				//img.show();
			}
			contactlink.set(event[2],event[3]);
		});
	}
	/*
	 * setEvent
	 * Takes a contact by its pid.
	 * Takes a message by its mid.
	 *
	 * The HTML ids for the are concatinated with 'c-' for a contact and something else for message
	 */

	function setEvent(contactid,messageid,category,isChecked,comment) {
		//var category = tabs.currentSection.substr(4); // strip 'lib-'

		if(checkedcache.get(contactid) == undefined){
			checkedcache.set(contactid,new Hash());
		}
		var contactlink = checkedcache.get(contactid);
		var img = $('c-' + contactid + '-' + category);
		if(isChecked) {
			if(contactlink.get(messageid) == undefined || comment !== false) {
				if(img != undefined) {
					img.src = categoryinfo.get(category).img;
				}
				contactlink.set(messageid,comment);
			}
		} else {
			contactlink.unset(messageid);
			if(contactlink.size() == 0) {
				if(img != undefined) {
					img.src = 'img/pixel.gif';
				}
				checkedcache.unset(contactid);
			}
		}
	}


	 function saveComment(id) {
		var prefix = tabs.currentSection == 'lib-search'?'smsg':'msg';
		var text = $(prefix+ 'rem' + id).down('textarea').getValue();
		// Save event to database
		new Ajax.Request(requesturl,
		{
			method:'post',
			parameters: {eventContacts: checkedcontacts.keys().toJSON(),
						eventMessage: id,
						isChecked: true,
						eventComments:text,
						sectionid:$('classselect').getValue()},
			onSuccess: function(response){
				if (response.responseText.indexOf(" Login</title>") != -1) {
					alert('Your changes cannot be saved because your session has expired or logged out.');
					window.location="index.php?logout=1";
				}
				if(response.responseJSON == false) {
					alert('Unable to save remark');
				}
				else {
					var cat = $(prefix + '-' + id).readAttribute('category');
					checkedcontacts.each(function(contact) {
						setEvent(contact.key,id,cat,true,text);
					});
					$(prefix + 'rem' + id).hide();
					$(prefix + '-' + id).down('a').show();
					$(prefix + '-' + id).setStyle("height:4.5em;")
					$(prefix + 'txt-' + id).setStyle("height:3em;")
					remarkpreview(prefix,id,text);
				}
			}
		});
	 }

	// has link in this section only
	function haslink(contact,message) {
		if(checkedcache.get(contact) == undefined || checkedcache.get(contact).get(message) == undefined)
			return false;
		return true;
	}


	function markcomment(prefix,id) {
		var previous;
		if(markedcomment == id) {
			previous = $(prefix + id);
			$(prefix + id).setStyle('border-color:silver');
			markedcomment = false;
		} else {
			if(markedcomment)
				previous = $(prefix + markedcomment);
			$(prefix + id).setStyle('border-color:red');
			markedcomment = id;
		}

		if(previous != undefined) {
			previous.setStyle('border-color:silver');
		}
		markedcontacts.each(function(contact) {
			$('c-' + contact.key).setStyle('border:0px');
		});

		if(markedcomment) {
			checkedcache.each(function(contact) {
				if(contact.value.get(markedcomment) != undefined) {
					$('c-' + contact.key).setStyle('border:1px solid red;');
					markedcontacts.set(contact.key,true);
				}
			});
		}
	}
	function remarkpreview(prefix,id,remark) {
		if(remark.length > 20)
			remark = remark.substring(0, 20) + '...';
		$(prefix + 'prem' + id).update(remark);
	}

	function highlight(obj) {
		//obj.addClassName('listAlt');
		obj.style.background = c_selected;
	}

	function clearhighlight(obj) {
		obj.style.background = c_none;

		//obj.removeClassName('listAlt');
	}

	function dosearch() {
		new Ajax.Request(requesturl,
			{
				method:'get',
				parameters: {search: $('searchbox').getValue()},
				onSuccess: function(transport){
					var response = transport.responseJSON || false;
					if(response) {
						var container = new Element('div');
						var messages = $H(response);
						var prevcategory = false;

						messages.each(function(itm) {
							//all += itm.key + " " + itm.value + '\n';
							if(itm.value.categoryid != prevcategory) {
								var cat = categoryinfo.get(itm.value.categoryid);
								container.insert('<div class="searchcategory"><img src="' + cat.img  + '" />&nbsp;' + cat.name +'</div><div style="clear:both;"></div>');
								prevcategory = itm.value.categoryid;
							}
							container.insert(
								'<div id="smsg-' + itm.key  + '" class="classroomcomment" category="' + itm.value.categoryid +'">' +
								'<img id="smsgchk-' + itm.key  + '" class="msgchk" src="img/checkbox-clear.png" alt=""/>' +
								'<div id="smsgtxt-' + itm.key  + '" class="msgtxt" >' + itm.value.title +
								' </div>' +
								'<img src="img/icons/fugue/marker.gif" alt="Mark" title="Mark this Comment" style="float:right;margin:2px" onclick="markcomment(\'smsg-\',\'' + itm.key + '\')" />' +
								'<div style="clear:both;">' +
									(hascomments?'<div id="smsgprem' + itm.key + '" class="remarklink"></div><a href="#" class="remarklink">Remark</a>':'&nbsp;') +
								'</div>' +
								'<span id="smsgrem' + itm.key + '" class="remark" style="display:none;">' +
									'<textarea class="remark"></textarea>' +
									'<a class="remark" href="#" onclick="saveComment(\'' + itm.key + '\');false;">Done</a>' +
								'</span>' +
								'</div>'
							);
						});

						container.insert('<div style="clear:both;"></div>');
						$('searchResult').update(container);

						messages.each(function(itm) {
							var div = $('smsg-' + itm.key);
							div.observe('click',messageclick);
						});

						updatemessages('lib-search','lib-search');
					} else {
						$('searchResult').update('Not Found');
					}
				}
			});
	}


	function updateclock() {
		var now = new Date().getTime() / 1000;

		var diff = timetocutoff - now;

		if(diff > 0) {
			var hours = Math.floor(diff / 3600);
			var minutes = Math.floor(diff % 3600 / 60);
			$('clock').update((hours>0?hours + ' Hour' + (hours==1?' ':'s '):'') + minutes + ' Minute' + (minutes==1?' ':'s ') + ' left until cutoff');
		} else {
			clock.stop();
			alert('The cutoff time for this page has passed');
			window.location = '<?= $redirect ?>';
		}
	}


	function updatemessages(currenttab,nexttab) {
		var c_prefix = currenttab == 'lib-search'?'smsg':'msg';
		var n_prefix = nexttab == 'lib-search'?'smsg':'msg';

		var category = nexttab.substr(4);
		var contactsize = checkedcontacts.size();

		// Get all contact-message links from cache

		// Reset all previous message selections
		checkedmessages.each(function(msg) {

			var target = $(c_prefix + '-' + msg.key);
			if(target != undefined) {
				clearhighlight(target);
				target.setStyle('height:4.5em;');
				$(c_prefix + 'txt-' + msg.key).setStyle('height:3em;');

				var target = target.down('img');
				target.src = getstatesrc(0);
				if(hascomments) {
					target = $(c_prefix + 'prem' + msg.key);
					target.update('');
					target = target.next('a');
					target.show();
				}
				target =  $(c_prefix + 'rem' + msg.key);
				target.hide();
				target = target.down('textarea');
				target.stopObserving();
				target.setStyle({color: "black"});
				target.value = "";
			}
		});

		clearchecked();




		var selectedmessages = new Hash();
		var selectedcomments = new Hash();
		var newcontct = false;
		var nowedit = "";
		checkedcontacts.each(function(contact) {
			var messages = checkedcache.get(contact.key)
			nowedit += ', ' + $('c-' + contact.key).innerHTML;
			if(messages != undefined) {
				messages.each(function(msg) {
					if($(n_prefix + '-' + msg.key) != undefined) { // if searchtab is selected this may be undefined
						var count = selectedmessages.get(msg.key) || 0;
						count++;
						selectedmessages.set(msg.key,count);

						var comment = selectedcomments.get(msg.key) || false;
						if((msg.value != "" && msg.value === comment) || (comment === false && count == 1)) {
							selectedcomments.set(msg.key,msg.value);
						} else if((comment !== false && msg.value !== comment) || (comment === false && count > 1)) {
							selectedcomments.set(msg.key,true);
						}
					}
				});
			} else {
				newcontct = true;
			}
		});

		$('nowedit-' + category).update('Now Editing:' +  nowedit.substr(1));

		// Set all contact-message link boxes
		selectedmessages.each(function(msg) {
			var target = $(n_prefix + '-' + msg.key);
			highlight(target);
			target = target.down('img');
			if(msg.value == contactsize) {
				target.src = getstatesrc(2);
				checkedmessages.set(msg.key,2);
			} else {
				target.src = getstatesrc(1);
				checkedmessages.set(msg.key,1);
			}
			if(hascomments) {
				var comment = selectedcomments.get(msg.key) || false;
				var textarea = $(n_prefix + 'rem' + msg.key).down('textarea');
				if(comment === false) {
					textarea.value = "";
					remarkpreview(n_prefix,msg.key,"");
				} else if(comment === true || newcontct == true ) {
					textarea.value = "";
					// Add multiple remark notice
					remarkpreview(n_prefix,msg.key,'  * Multiple Remarks')
				} else {
					textarea.value = comment;
					remarkpreview(n_prefix,msg.key,comment);
				}
			}
		});
	}

	/*
	 * Get the class contacts and set observers
	 */
	function getclass(selected) {
		new Ajax.Request(requesturl,
		{
			method:'get',
			parameters: {sectionid: selected},
			onSuccess: function(transport){
				var response = transport.responseJSON || "Class not available";
				var contacts = $H(response.people);
				var size = 0;
				$('contactbox').update("");

				$('theinstructions').show();
				$('tabsContainer').hide();
				$('searchContainer').hide();

				clearcache();

				revealmessages = true;

				var dom = $('contactbox').remove();
				var tbody = new Element('tbody');

				contacts.each(function(person) {
					var id = 'c-' + person.key;
					var tr = new Element('tr');
					tr.insert('<td width="100%"><a href="#" id="' + id + '" title="Student id: ' +  person.value.pkey + '" style="text-decoration:none;cursor:pointer;">' + person.value.name +'</a></td>');
					var td = new Element('td', {style:'white-space:nowrap'});//{white-space:'nowrap'}
					categoryinfo.each(function(category) {
						td.insert('<img id="' + id + '-' + category.key + '"src="img/pixel.gif" title="' + category.value.name + '" style="width:10px;display:inline;" alt="" />');
					});
					tr.insert(td);
					tbody.insert(tr);
					size++;
				});
				dom.insert(new Element('table').insert(tbody));
				$('contactwrapper').insert(dom);


				contacts.each(function(person) {
					var id = 'c-' + person.key;
					dom.insert('<br />')

					$('contactwrapper').insert(dom);

					/*
					 * Observe Contact Click. Select one contact at a time or multiple contacts with
					 * alt key pressed.
					 */
					$(id).observe('click', function(event) {
							event.stop(); // Some browsers may open another winbdow on shift click
							if(!event.shiftKey) {
								checkedcontacts.each(function(contact) {
									clearhighlight($('c-' + contact.key));
									checkedcontacts.unset(contact.key);
								});
							}

							// Select or deselect the itme depending on alt click. Unable to deselect if only one item is selected
							if (event.shiftKey && checkedcontacts.get(this.id.substr(2)) != undefined && checkedcontacts.size() > 1) {
								clearhighlight(this);
								checkedcontacts.unset(this.id.substr(2));
							} else {
								highlight(this);
								checkedcontacts.set(this.id.substr(2),true);
							}
							// First click reveals the message board
							if(revealmessages) {
								revealmessages = false;
								$('theinstructions').hide();
								$('tabsContainer').show();
								$('searchContainer').show();
							}
							updatemessages(tabs.currentSection,tabs.currentSection);
					});
				});
				if(size > 0) {
					setcache(response.cache);
					$('picker').show();
				} else {
					$('picker').hide();
				}å
			}
		});
	}

	function messageclick(event) {
		event.stop();
		var htmlid = this.id;  // html id: message-category-mid
		var c_prefix = this.id.substr(0,4) == "smsg"?"smsg":"msg";
		var msgid = this.id.substr(c_prefix == "smsg"?5:4);

		var state = checkedmessages.get(msgid) || 0;
		var element = event.element();


		if(element.hasClassName && element.hasClassName('remarklink')) {
			if($(c_prefix + 'rem' + msgid).down('textarea').getValue() == '' && $(c_prefix + 'prem' + msgid).innerHTML != '') {
				if(!confirm('Multiple Remarks. Edeting will replace all previous remarks.'))
					return;
			}

			$(c_prefix + 'txt-' + msgid).setStyle('height:auto');
			event.target.hide();
			$(htmlid).setStyle('height:auto');
			var target = $(c_prefix + 'rem' + msgid);
			target.show();
			$(c_prefix + 'prem' + msgid).update('');
			target = target.down('textarea');
			target.focus();
			target.blur();
			if(state == 2) {
				return;
			}
		}

		// Don't modify anything if writing a comment'
	//	if(!event.target.hasClassName('remark')) {
		if(element.hasClassName && (element.hasClassName('msgtxt') || element.hasClassName('msgchk') || element.hasClassName('remarklink'))) {

			state = (state == 2)? 0 : 2;
			var textarea = $(c_prefix + 'rem' + msgid).down('textarea');
			if((state == 0) && (hascomments && textarea.getValue() != '' || $(c_prefix + 'prem' + msgid).innerHTML != '')) {
				if(!confirm('The custom remark will be removed if unselecting.'))
					return;
				else {
					textarea.value = "";
					$(c_prefix + 'prem' + msgid).update("");
					textarea.stopObserving();
				}
			}

			// Save event to database
			new Ajax.Request(requesturl,
			{
				method:'post',
				parameters: {eventContacts: checkedcontacts.keys().toJSON(),
							eventMessage: msgid,
							isChecked:(state == 2),
							sectionid:$('classselect').getValue()},
				onFailure: function(){ alert('Unable to Set Message') },
				onException: function(){ alert('Unable to Set Message') },
				onSuccess: function(response) {
					if (response.responseText.indexOf(" Login</title>") != -1) {
						alert('Your changes cannot be saved because your session has expired or logged out.');
						window.location="index.php?logout=1";
					}

					$(htmlid).down('img').src = getstatesrc(state);
					checkedmessages.set(msgid,state);                  // Set Message to appropriate state
					// Set each selected contact to

					if(state == 2) {
						highlight($(htmlid));
					} else {
						var target = $(htmlid);
						clearhighlight(target);
						target.setStyle('height:4.5em;');//background:' + c_none);
						$(c_prefix + 'txt-' + msgid).setStyle('height:3em;');
						target.down('span').hide();
						target.down('a').show();
					}

					checkedcontacts.each(function(contact) {
						if(state == 2) {
							highlightedcontacts.set('c-' + contact.key,true);
							if(markedcomment == msgid) {
								markedcontacts.set(contact.key,true);
								$('c-' + contact.key).setStyle('border:1px solid red;');
							}
						} else {
							if(markedcomment == msgid) {
								markedcontacts.unset(contact.key);
								$('c-' + contact.key).setStyle('border:1px solid white;');
							}
							highlightedcontacts.unset('c-' + contact.key);
						}
						setEvent(contact.key,msgid,$(htmlid).readAttribute('category'),(state == 2),false);
					});
				}
			});
		}
	};

	document.observe("dom:loaded", function() {

		getclass($('classselect').getValue());

		$('picker').observe("selectstart", function(event) {          // disable select in IE
			if(event.target.hasClassName && !(event.target.hasClassName('remark') || event.target.hasClassName('searchbox')))
				event.stop();
		});
		$('picker').observe("mousedown", function(event) {			  // disable select in FF
			if(event.target.hasClassName && !(event.target.hasClassName('remark') || event.target.hasClassName('searchbox')))
				event.stop();
		});

		/*
		 * Static observers
		 */
		$('checkall').observe('click', function(event) {
			event.stop();
			$$('#contactbox a').each(function(contact) {
				highlight(contact);
				checkedcontacts.set(contact.id.substr(2),true);
			});
			if(revealmessages) {
				$('theinstructions').hide();
				revealmessages = false;
				$('tabsContainer').show();
				$('searchContainer').show();

			}
			updatemessages(tabs.currentSection,tabs.currentSection);
		}.bindAsEventListener($('contactbox')));

		$('classselect').observe('change', function(event) {
			event.stop();
			getclass(event.element().getValue());
		});

		$$('#libraryContent .classroomcomment').each(function(message) {
			message.observe('click', messageclick);

			message.observe('mouseover', function(event) {
				event.stop();
				//var htmlid = this.id;
				var msgid = this.id.substr(4);  // strip 'msg-'
				var category = tabs.currentSection;
				if(category == 'lib-search')
					return
				var categorystr = category.substr(4);
				checkedcache.each(function(contact) {
					if(contact.value.get(msgid) != undefined) {
						var img = $('c-' + contact.key + '-' + categorystr);
						Effect.Queues.get(img.id).each(function(effect) { effect.cancel(); });
						new Effect.Pulsate(img,{pulses:2, from:0.5, duration: 1.5, queue: { position: 'end', scope: img.id }});
						highlightedcontacts.set('c-' + contact.key,true);
					}
				});
			});

			message.observe('mouseout', function(event) {
				event.stop();
				var category = tabs.currentSection;
				if(category == 'lib-search')
					return
				var categorystr = category.substr(4);
				highlightedcontacts.each(function(contact) {
					var img = $(contact.key + '-' + categorystr);
					Effect.Queues.get(img.id).each(function(effect) { effect.cancel();});
					img.style.opacity = 1.0;
					highlightedcontacts.unset(contact.key);
				});
			});
		});


		// Load tabs
		tabs = new Tabs('tabsContainer',{hideDuration:0,showDuration:0});

		categoryinfo.each(function(category) {
			checkedcache.set(category.key,new Hash());
			var conentid = "lib-" + category.key;

			tabs.add_section(conentid);
			tabs.update_section(conentid, {
				"title": category.value.name,
				"icon": category.value.img,
				"content": $(conentid).remove()
			});
		});
		tabs.add_section('lib-search');
		tabs.update_section('lib-search', {
			"title": 'Search',
			"icon": 'img/magnify.gif',
			"content": $('lib-search').remove()
		});

		tabs.show_section('lib-' + categoryinfo.keys().first());

		tabs.container.observe('Tabs:ClickTitle', function(event) {
			if(event.memo.currentSection == 'lib-search' || event.memo.section == 'lib-search'){
				updatemessages(event.memo.currentSection,event.memo.section);

				var searchBox = $('searchbox');
				//searchBox.focus();
				searchBox.blur();
			} else{
				$('nowedit-' + event.memo.section.substr(4)).update($('nowedit-' + event.memo.currentSection.substr(4)).innerHTML);
			}
		});
		var searchBox = $('searchbox');

		searchBox.observe('keypress', function(event) {
			if (Event.KEY_RETURN == event.keyCode)
				dosearch();
		});
		blankFieldValue(searchBox, "Search Comments");

		clock = new PeriodicalExecuter(updateclock,5);
		updateclock();
	});

