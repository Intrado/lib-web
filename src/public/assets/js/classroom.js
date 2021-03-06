
	// Color variables
	var c_hover = "#bbcccc";
	var c_selected = "#C4CCC4";//"#ffcccc";
	var c_none = "#ffffff";
	var h_image = "assets/img/icons/fugue/arrow.gif";

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
	var progresstack = new Array();

	var tabs;
	var clock;

	function getstatesrc(state) {
		switch (state) {
			case 0:
				return "assets/img/checkbox-clear.png";
			case 1:
				return "assets/img/checkbox-dash.png";
			case 2:
				return "assets/img/checkbox-check.png";
		}
		return "";
	}

	function clearcache() {
		checkedcache = new Hash()
		checkedcontacts = new Hash();
	}

	function clearchecked() {
		checkedmessages = new Hash();
		progresstack = new Array();
	}

	function setcache(cache) {
		var marked = $(markedcomment);
		if(marked && marked != undefined) {
			marked.setStyle('border-color:silver');
		}
		markedcontacts = new Hash();

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
				var title = img.title;
				img.title = (parseInt(title) + 1)  + img.title.substr(title.indexOf(" "));
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
			var link = contactlink.get(messageid);
			if(link == undefined || comment !== false) {
				if(link == undefined && img != undefined) {
					img.src = categoryinfo.get(category).img;
					var title = img.title;
					img.title = (parseInt(title) + 1)  + img.title.substr(title.indexOf(" "));
				}
				contactlink.set(messageid,comment);
			}
		} else {
			contactlink.unset(messageid);
			if(img != undefined) {
				var title = img.title;
				var count = (parseInt(title) - 1);
				img.title = count  + img.title.substr(title.indexOf(" "));
				if(count == 0)
					img.src = 'assets/img/pixel.gif';
			}
			if(contactlink.size() == 0) {
				checkedcache.unset(contactid);
			}
		}
	}


	 function saveComment(id) {
		var prefix = tabs.currentSection == 'lib-search'?'smsg':'msg';
		var text = $(prefix+ 'rem' + id).down('textarea').getValue();
		if(text.length > 5000) {
			alert('Remarks must contain less than 5000 characters.');
			return;
		}

		// Save event to database
		new Ajax.Request(requesturl,
		{
			method:'post',
			parameters: {eventContacts: Object.toJSON(checkedcontacts.keys()),
						eventMessage: id,
						isChecked: true,
						eventComments:text,
						sectionid:$('classselect').getValue()},
			onSuccess: function(response,cuurentcontacts){
				if (response.responseText.indexOf(" Login</title>") != -1) {
					alert('Your changes cannot be saved because your session has expired or logged out.');
					window.location="index.php?logout=1";
				}
				if(response.responseJSON == false) {
					alert('Unable to save remark');
				}
				else {
					var cat = $(prefix + '-' + id).readAttribute('category');
					cuurentcontacts.each(function(contact) {
						setEvent(contact,id,cat,true,text);
					});

					if(Object.toJSON(checkedcontacts.keys()) != Object.toJSON(cuurentcontacts)) {
						updatemessages(tabs.currentSection,tabs.currentSection);
					} else {
						$(prefix + 'rem' + id).hide();
						$(prefix + '-' + id).down('a').show();
						$(prefix + '-' + id).setStyle("height:4.5em;")
						$(prefix + 'txt-' + id).setStyle("height:3em;")
						remarkpreview(prefix,id,text);
					}
				}
			}.bindAsEventListener(this, checkedcontacts.keys())
		});
	 }

	function markcomment(prefix,id) {
		var previous;
		if(markedcomment == (prefix + id)) {
			previous = $(prefix + id);
			$(prefix + id).setStyle('border-color:silver');
			markedcomment = false;
		} else {
			if(markedcomment)
				previous = $(markedcomment);
			$(prefix + id).setStyle('border-color:red');
			markedcomment = prefix + id;
		}

		if(previous != undefined) {
			previous.setStyle('border-color:silver');
		}
		markedcontacts.each(function(contact) {
			$('c-' + contact.key).setStyle('border:0px');
		});

		if(markedcomment) {
			var markedid = markedcomment.substr(markedcomment.indexOf('-')+1);
			checkedcache.each(function(contact) {
				if(contact.value.get(markedid) != undefined) {
					$('c-' + contact.key).setStyle('border:1px solid red;');
					markedcontacts.set(contact.key,true);
				}
			});
		}
	}
	function remarkpreview(prefix,id,remark) {
		if(remark.length > 20)
			remark = remark.substring(0, 20) + '...';
		$(prefix + 'prem' + id).update(remark.escapeHTML());
		$(prefix + 'prem' + id).show();
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
						//var container = new Element('div');
						var messages = $H(response);
						var prevcategory = false;

						// Empty out the search container
						$('searchResult').update('');

						// Make a new category container that will hold all the results for the current category
						var category = new Element('div', {'class': 'clearfix'});
						messages.each(function(itm) {
							//all += itm.key + " " + itm.value + '\n';
							// If the category for this result is different from the one we've been working on...
							if(itm.value.categoryid != prevcategory) {

								// If the category container has some results in it
								if (! category.empty()) {
									// append it to the main container
									$('searchResult').insert(category);
								}

								// Empty out the category container once again
								category = new Element('div', {'class': 'clearfix'});

								var cat = categoryinfo.get(itm.value.categoryid);
								category.insert('<div class="searchcategory"><img src="' + cat.img  + '" />&nbsp;' + cat.name +'</div><br/>');
								prevcategory = itm.value.categoryid;
							}
							var comment = new Element('div',{id:'smsg-' + itm.key,'class':'classroomcomment',category:itm.value.categoryid});
							comment.insert(new Element('img',{id:'smsgchk-' + itm.key,'class':'msgchk',src:'assets/img/checkbox-clear.png',alt:''}));
							comment.insert(new Element('div',{id:'smsgtxt-' + itm.key,'class':'msgtxt'}).update(itm.value.title));
							comment.insert('<img src="assets/img/icons/fugue/marker.gif" alt="Mark" title="Mark this Comment" class="marker" onclick="markcomment(\'smsg-\',\'' + itm.key + '\')" />');
							var remarklink = new Element('div');

							if(hascomments) {
								remarklink.insert(new Element('div',{id:'smsgprem' + itm.key,'class':'remarklink'}));
								remarklink.insert(new Element('a',{href:'#','class':'remarklink'}).update('Remark'));
							}
							comment.insert(remarklink);
							var remarkbox = new Element('span',{id:'smsgrem' + itm.key,'class':'remark',style:'display:none;'});
							remarkbox.insert(new Element('textarea',{'class':'remark'}));
							remarkbox.insert(new Element('br'));
							remarkbox.insert('<a href="#" class="remark" onclick="saveComment(\'' + itm.key + '\');return false;">Done</a>');

							comment.insert(remarkbox);
							category.insert(comment);
						});

						// If the category container has some results in it
						if (! category.empty()) {
							// append it to the main container
							category.insert('<div style="clear:right;"></div>');
							$('searchResult').insert(category);
						}

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
			//alert('The cutoff time for classroom messaging page has passed.');
			window.location = 'classroommessageunautherized.php';
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

				target = target.down('img');
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

		progresstack.each(function(msg) {
			$(msg).down('img').src = 'assets/img/checkbox-clear.png';
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
	var currently_selected = null;
	function getclass(selected, orderby) {
		$('sectionloading').show();
		$('sectionloaded').hide();
		$('theinstructions').hide();

		new Ajax.Request(requesturl,
		{
			method:'get',
			parameters: {sectionid: selected, orderby: orderby},
			onSuccess: function(transport){
				var response = transport.responseJSON || "Class not available";
				var contacts = $(response.people);
				var size = 0;

				$('theinstructions').show();
				$('sectionloaded').show();
				$('sectionloading').hide();
				$('tabsContainer').hide();
				$('searchContainer').hide();

				// Only clear the cache if the selected class (secetion ID) has changed
				if (selected != currently_selected) clearcache();
				revealmessages = true;

				var tbody = new Element('tbody');

				contacts.each(function(person) {
					var id = 'c-' + person['pid'];
					var tr = new Element('tr');
					var name = (orderby == 'l') ? person['lastname'] + ', ' + person['firstname'] : person['firstname'] + ' ' + person['lastname'];
					tr.insert('<td width="100%"><a href="#" id="' + id + '" title="Student id: ' +  person['pkey'] + '" style="text-decoration:none;cursor:pointer;white-space: nowrap;">' + person['pkey'] + ' - ' + name +'</a></td>');

					// Only create new/default per-category indicators if the class (section ID) has changed
					var statsid = 'stats-' + person['pid'];
					var td = new Element('td', {style: 'white-space:nowrap'});
					if (selected != currently_selected) {
						categoryinfo.each(function(category) {
							td.insert('<img id="' + id + '-' + category.key + '"src="assets/img/pixel.gif" title="0 Comment(s) for ' + category.value.name + '" style="width:10px;display:inline;" alt="" />');
						});
					}
					else {
						// Otherwise, we'll copy the status indicators from the existing DOM
						var t = $$('td[title='+statsid+']')[0].innerHTML;
						td.innerHTML = t;
					}
					td.setAttribute('title', statsid);
					tr.insert(td);

					tbody.insert(tr);
					size++;
				});

				$('contactbox').update("");
				var dom = $('contactbox').remove();
				dom.insert(new Element('table').insert(tbody));
				$('contactwrapper').insert(dom);

				contacts.each(function(person) {
					var id = 'c-' + person['pid'];
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

							var contactid = this.id.substr(2);

							// Select or deselect the itme depending on alt click. Unable to deselect if only one item is selected
							if (event.shiftKey && checkedcontacts.get(contactid) != undefined && checkedcontacts.size() > 1) {
								clearhighlight(this);
								checkedcontacts.unset(contactid);
							} else {
								highlight(this);
								checkedcontacts.set(contactid,true);
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
				}

				currently_selected = selected;
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
				if(!confirm('Multiple Remarks. Editing will replace all previous remarks.'))
					return;
			}
			$(c_prefix + 'prem' + msgid).next('a').hide();
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
			if((state == 0) && hascomments && (textarea.getValue() != '' || $(c_prefix + 'prem' + msgid).innerHTML != '')) {
				if(!confirm('The custom remark will be removed if unselecting.'))
					return;
				else {
					textarea.value = "";
					$(c_prefix + 'prem' + msgid).update("");
					textarea.stopObserving();
				}
			}
			$(htmlid).down('img').src = 'assets/img/ajax-loader.gif';
			progresstack.push(htmlid);


			// Save event to database
			new Ajax.Request(requesturl,
			{
				method:'post',
				parameters: {eventContacts: Object.toJSON(checkedcontacts.keys()),
							eventMessage: msgid,
							isChecked:(state == 2),
							sectionid:$('classselect').getValue()},
				onFailure: function(){ alert('Unable to Set Message') },
				onException: function(){ alert('Unable to Set Message') },
				onSuccess: function(response,cuurentcontacts) {
					if (response.responseText.indexOf(" Login</title>") != -1) {
						alert('Your changes cannot be saved because your session has expired or logged out.');
						window.location="index.php?logout=1";
					}
					if(response.responseJSON == false) {
						alert('Unable to set comment');
						$(htmlid).down('img').src = getstatesrc(checkedmessages.get(msgid) || 0);
						return;
					}
					var markedid = markedcomment?markedcomment.substr(markedcomment.indexOf('-')+1):'';

					cuurentcontacts.each(function(contact) {
						if(state == 2) {
							//highlightedcontacts.set('c-' + contact.key,true);
							if(markedid == msgid) {
								markedcontacts.set(contact,true);
								$('c-' + contact).setStyle('border:1px solid red;');
							}
						} else {
							if(markedid == msgid) {
								markedcontacts.unset(contact);
								$('c-' + contact).setStyle('border:1px solid white;');
							}
							//highlightedcontacts.unset('c-' + contact.key);
						}
						setEvent(contact,msgid,$(htmlid).readAttribute('category'),(state == 2),false);
					});
					if(Object.toJSON(checkedcontacts.keys()) != Object.toJSON(cuurentcontacts)) {
						updatemessages(tabs.currentSection,tabs.currentSection);
					} else {
						$(htmlid).down('img').src = getstatesrc(state);
						checkedmessages.set(msgid,state);                  // Set Message to appropriate state
						// Set each selected contact to

						if(state == 2) {
							highlight($(htmlid));
						} else {
							var target = $(htmlid);
							clearhighlight(target);
							target.setStyle('height:4.5em;');
							$(c_prefix + 'txt-' + msgid).setStyle('height:3em;');
							target.down('span').hide();
							target.down('a').show();
						}
					}
				}.bindAsEventListener(this, checkedcontacts.keys())
			});
		}
	};

	function form_getclass() {
		var sectionid = $('classselect').getValue();

		// Here's how you get the value of the checked radio button in a radio button group with prototypejs
		// ref: http://stereointeractive.com/blog/2008/06/05/get-radio-button-value-using-prototype/
		var orderby = $$('input:checked[type="radio"][name="orderby"]').pluck('value');

		getclass(sectionid, orderby);
	}

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
			form_getclass();
		});

		// Watch for changes to the order by:
		//$('input:[type="radio"][name="orderby"]').observe('check', function (event) {
		$('orderbys').on('change', '.orderbys', function (event) {
			event.stop();
			form_getclass();
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
						new Effect.Pulsate(img,{pulses:100, from:0.5, duration: 200, queue: { position: 'end', scope: img.id }});
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
			"icon": 'assets/img/magnify.gif',
			"content": $('lib-search').remove()
		});

		tabs.show_section('lib-' + categoryinfo.keys().first());

		tabs.container.observe('Tabs:ClickTitle', function(event) {
			if(event.memo.currentSection == 'lib-search' || event.memo.section == 'lib-search'){
				updatemessages(event.memo.currentSection,event.memo.section);
				var searchBox = $('searchbox');
				searchBox.blur();
			} else{
				$('nowedit-' + event.memo.section.substr(4)).update($('nowedit-' + event.memo.currentSection.substr(4)).innerHTML);
			}
			// clear markers
			if(markedcomment) {
				$(markedcomment).setStyle('border-color:silver');
				markedcomment = false;
			} 
			markedcontacts.each(function(contact) {
				$('c-' + contact.key).setStyle('border:0px');
			});

			new Ajax.Request(requesturl,{method:'post',parameters:{settab:event.memo.section.substring(4)}});
		});
		
		load_saved_tab();

		var searchBox = $('searchbox');

		searchBox.observe('keypress', function(event) {
			if (Event.KEY_RETURN == event.keyCode)
				dosearch();
		});
		blankFieldValue(searchBox, "Search Comments");

		clock = new PeriodicalExecuter(updateclock,5);
		updateclock();
	});

