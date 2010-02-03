<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Event.obj.php");
require_once("obj/Alert.obj.php");
require_once("obj/TargetedMessageCategory.obj.php");
require_once("obj/TargetedMessage.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('targetedmessage')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_POST['eventContacts']) && isset($_POST['eventMessage']) && isset($_POST['isChecked'])) {
	$contacts = json_decode($_POST['eventContacts']);
	$message = $_POST['eventMessage'];
	$comment = isset($_POST['eventComments'])?$_POST['eventComments']:"";
	
	$ischecked = $_POST['isChecked'];
	if($ischecked == "false") {
		$args = array($USER->id,$message);
		foreach($contacts as $contact) {
			$args[] = $contact;
		}
		$eventids = QuickQueryList("select e.id from personassociation pa left join event e on (pa.eventid = e.id) where e.userid = ? and e.targetedmessageid = ? and Date(e.occurence) = CURDATE() and pa.personid in (" . repeatWithSeparator("?",",",count($contacts)) . ")",
						false,false,$args);
		if (count($eventids) > 0) {
			$idstr = implode(",",$eventids);
			QuickQuery("BEGIN");
			QuickQuery("delete from alert where eventid in (" . $idstr . ")");
			QuickQuery("delete from personassociation where eventid in (" . $idstr . ")");
			QuickQuery("delete from event where id in (" . $idstr . ")");
			QuickQuery("COMMIT");
		}
		exit();
  	}

	foreach($contacts as $contact) {
		QuickQuery("BEGIN");
		// Query to see if the event is recorded
		$eventid = QuickQuery("select e.id from personassociation pa left join event e on (pa.eventid = e.id) where pa.personid = ? and e.userid = ? and e.targetedmessageid = ? and Date(e.occurence) = CURDATE()",
						false,array($contact,$USER->id,$message));
		$event = null;
		if($eventid === false) {
			$event = new Event();
		} else {
			$event = new Event($eventid);
		}
		$event->userid = $USER->id;
		$event->organizationid = 1; //TODO implement organization stuff
		$event->sectionid = 1;  //TODO implement section stuff
		$event->targetedmessageid = $message;
		$event->name = "placeholder: TODO"; // TODO get name from targetedmessage
		if(isset($_POST['eventComments']))
			$event->notes = $USER->authorize('targetedmessage')?$_POST['eventComments']:"";
		else if(!$event->notes)
			$event->notes = "";
		$event->occurence = date("Y-m-d H:i:s");
		$event->update();

		// Get the alert since this is a targeted message there should only be one alert
		$alert = ($eventid !== false)?new Alert(QuickQuery("select id from alert where eventid = ?",false,array($event->id))):new Alert();		
		$alert->eventid = $event->id;
		$alert->personid = $contact;
		$alert->date = date("Y-m-d");
		$alert->time = date("H:i:s");
		$alert->update();

		if($eventid === false) {
			QuickQuery("insert into personassociation values (?,?,?,?,?)",false,array($contact,'event',NULL,NULL,$event->id));
		}
		QuickQuery("COMMIT");

	}
	exit(0);
}

//Handle ajax request. when swithcing sections
if (isset($_GET['sectionid'])) {
	header('Content-Type: application/json');
	$id = $_GET['sectionid'];
	$response = false;

	$usersection = QuickQuery("select count(*) from userassociation ua where sectionid = ? and userid = ?",
							false,array($id,$USER->id));
	if($usersection > 0) {
		// User can access this section

		$res = Query("select p.id, p.pkey,concat(p.f01,'&nbsp;', p.f02) name
											from person p join personassociation pa on (p.id = pa.personid)
											where pa.type = 'section' and sectionid = ?",false,array($id));
		while($row = DBGetRow($res)){
			$obj = null;
			$obj->pkey = $row[1];
			$obj->name = $row[2];
			$response->people[$row[0]] = $obj;
		}
		if(count($response->people) > 0) {
			$contactids = array_keys($response->people);
			$query = "select tm.targetedmessagecategoryid, pa.personid, e.targetedmessageid, e.notes from
					 personassociation pa left join event e on (pa.eventid = e.id)
					 left join targetedmessage tm on (e.targetedmessageid = tm.id)
					 where e.targetedmessageid is not null and e.userid = ? and Date(e.occurence) = CURDATE() and pa.personid in (" . implode(",",$contactids) . ")";
			$response->cache = QuickQueryMultiRow($query,false,false,array($USER->id));
		} else {
			$response = false;
		}

	}


	error_log(json_encode($response));
	echo json_encode($response);
	exit(0);
}

$sections = DBFindMany("Section", "from section s join userassociation ua on (ua.sectionid = s.id) where ua.userid = ?","s",array($USER->id));

$categories = QuickQueryMultiRow("select id, name, image from targetedmessagecategory where deleted = 0",true);
$categoriesjson = array();
foreach($categories as $category) {
	$obj = null;
	$obj->name = $category["name"];
	if(isset($category["image"]) && isset($classroomcategoryicons[$category["image"]]))
		$obj->img = "img/icons/" . $classroomcategoryicons[$category["image"]]  . ".gif";
	else
		$obj->img = "img/pixel.gif";
	$categoriesjson[$category["id"]] = $obj;
}

$categories = array(0 => "Positive",1 => "Corrective",2 => "Informational");

$library = array();
$customtxt = array();
foreach($categoriesjson as $id => $obj) {
	$library[$id] = DBFindMany("TargetedMessage","from targetedmessage where enabled = 1 and targetedmessagecategoryid = ?",false,array($id));
	$customtxt[$id] = QuickQueryList("select t.id, p.txt from targetedmessage t, message m, messagepart p
										where t.targetedmessagecategoryid = ? and t.deleted = 0 and
											t.overridemessagegroupid = m.messagegroupid and
											m.languagecode = 'en' and
											p.messageid = m.id and p.sequence = 0",true,false,array($id));
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:classroom";
$TITLE = _L('Classroom Comments');

include_once("nav.inc.php");


echo '<select id="classselect" name="classselect" style="margin:5px">';
foreach($sections as $section) {
	echo '<option value="'.$section->id.'">'.escapehtml($section->skey).'</option>';
}
echo "</select>";

startWindow(_L('Classroom Comments'));

?>

<table width="100%" id="picker">
	<tr>
		<td style="top:0px;width:250px;vertical-align:top;border-right:1px solid black;padding-right:10px;">
			<a id="checkall" href="#" style="float:left; white-space: nowrap;">Select All</a><br />
			<div id="contactwrapper">
				<div id="contactbox" style="width:100%;text-decoration:none;"></div>
			</div>
			<hr />
			<img src="img/icons/fugue/light_bulb.gif" alt="" />Press shift key to multiselect
			<hr />
		</td>

		<td style="vertical-align:top;">
			<div id="theinstructions" style="font-size:2em;padding:100px;"><img src="img/icons/fugue/arrow_180.png" alt="" style="vertical-align:middle;"/>&nbsp;Click on a Contact to Start</div>

		<div id="searchContainer" style="margin:10px;display:none;"><input id="searchbox" type="text" value="" size="50" style="float:left"/><?= icon_button("Search", "magnifier", null, null)  . icon_button("Done Picking Comments", "tick", null, "classroommessageoverview.php",'style="float:right;"') ?></div>
			<div style="clear:both;"></div>
			<div id='tabsContainer' style=' margin:10px; margin-right:0px;display:none;vertical-align:middle;'></div>

			<div id="libraryContent" style="display:none;">
			<?
				$libraryids = array();
				$messageids = array();
				$filename = "messagedata/en/data.php";
				if(file_exists($filename))
					include_once($filename);
				foreach($library as $categoryid => $messages) {
					// add library to id since user may change the title of the category
					echo "<div id='lib-$categoryid' style='display:block;'>";
					foreach($messages as $message) {
		
						if(isset($message->overridemessagegroupid) && isset($customtxt[$categoryid][$message->id])) {
							$title = $customtxt[$categoryid][$message->id];
						} else if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$message->messagekey])) {
							$title = $messagedatacache["en"][$message->messagekey];
						} else {
							$title = ""; // Could not find message for this message key.
						}
						// TODO make proper css
						echo '<div id="msg-' . $message->id .'" class="targetmessage" style="position:relative;border:solid 1px silver;background-color:#FFF;width:300px;height:50px;float:left;margin:4px;padding:0px;">
								<img src="img/checkbox-clear.png" alt="" style="float:left;margin:2px"/>
								<div id="msgtxt-' . $message->id .'" style="float:left;overflow:auto;height:35px;width:260px">'
								. $title .
								' </div>
								<img src="img/icons/fugue/marker.gif" alt="" style="float:right;margin:2px"/>

								<div style="clear:both;"></div>' .
								($USER->authorize('targetedcomment')?'<a href="#" class="commentlink" style="float:right">Remark</a>':'&nbsp;') .
								'
								<span id="com-' . $message->id .'" class="targetcomment" style="width:280px;border:0px solid black;padding:10px;height:100px;bottom:0px;display:none;">
									<textarea class="targetcomment" style="height:70px;width:265px;"></textarea>
									<a class="targetcomment" href="#" style="" onclick="saveComment(\''. $message->id .'\');false;">Done</a><span style="display:none"></span>
								</span>
								</div>';
					}
					echo '<div style="clear:both;"></div></div>';
				}
			?>
			</div>
		</td>
	</tr>
</table>


<?
endWindow();


// TODO --- Once development is done script should move to its own file for caching purposes 
?>
<script type="text/javascript" src="script/accordion.js"></script>
<script type="text/javascript" language="javascript">

	// Color variables
	var c_hover = "#bbcccc";
	var c_selected = "#ffcccc";
	var c_none = "#ffffff";
	var h_image = "img/icons/fugue/arrow.gif";
	var hascomments = <?= $USER->authorize('targetedcomment')?"true":"false" ?>;

	/*
	 Checked cache contains
	 Checkedcache -> Category -> Contact -> Message

	 ie. Checkedcache -> "Positive" -> "p_0001" -> "m_0001"

	 Checkedcache has an extra level Category to easaly determine if a contact has one or more messages checked in under one category
	*/
	var checkedcache = new Hash();			// History of Contact to Message links


	var categoryinfo = $H(<?= json_encode($categoriesjson) ?>);

	var checkedcontacts = new Hash();		// List of the Contacts that are currently checked
	var checkedmessages = new Hash();		// List of Messages that are currently checked
	var highlightedmessages = new Hash();	// List of Messages that are currently highlighted
	var highlightedcontacts = new Hash();	// List of Contacts that are currently highlighted

	var revealmessages = true;			// Boolean to reveal messages on first click

	var tabs;

	function getstatesrc(state) {
		switch(state){
			case 0:
				return "img/checkbox-clear.png";
			case 1:
				return "img/checkbox-add.png";
			case 2:
				return "img/checkbox-check.png";
		}
		return "";
	}

	function clearcache() {
		checkedcache.each(function(category) {
			checkedcache.set(category.key,new Hash());
		});
		checkedcontacts = new Hash();
	}

	function setcache(cache) {
		cache.each(function(event) {
			var people = checkedcache.get(event[0]);
			var contactid = event[1];

			if(people.get(contactid) == undefined)
				people.set(contactid,new Hash());
			var contactlink = people.get(contactid);

			var img = $('c-' + contactid + '-' + event[0]);
			if(img != undefined) {

				img.show();
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

	function setEvent(contactid,messageid,isChecked,comment) {
		var category = tabs.currentSection.substr(4); // strip 'lib-'
		var people = checkedcache.get(category);
		if(people.get(contactid) == undefined){
			people.set(contactid,new Hash());
		}
		var contactlink = people.get(contactid);
		if(isChecked) {

			var img = $('c-' + contactid + '-' + category);
			if(img != undefined) {
				img.show();
			}
			contactlink.set(messageid,comment);
		} else {
			contactlink.unset(messageid);
			if(contactlink.size() == 0) {
				var img = $('c-' + contactid + '-' + category);
				if(img != undefined) {
					img.hide();
				}
				people.unset(contactid);
			}
		}
	}
	 function saveComment(id) {
		var text = $('msg-' + id).down('textarea').getValue();
		// Save event to database
		new Ajax.Request('classroommessage.php',
		{
			method:'post',
			parameters: {eventContacts: checkedcontacts.keys().toJSON(),
						eventMessage: id,
						isChecked: true,
						eventComments:text},
			onSuccess: function(transport){
				checkedcontacts.each(function(contact) {
					setEvent(contact.key,id,true,text);
				});
				$('com-' + id).hide();
				$('msg-' + id).down('a').show();
				$('msg-' + id).setStyle("height:50px;")
				$('msgtxt-' + id).setStyle("height:35px;")

			}
		});
	 }

	// has link in this section only
	function haslink(contact,message) {
		if(checkedcache.get(tabs.currentSection).get(contact) == undefined || checkedcache.get(tabs.currentSection).get(contact).get(message) == undefined)
			return false;
		return true;
	}

	function updatemessages(currenttab) {
		var contactsize = checkedcontacts.size();


		var category = currenttab.substr(4);
		// Get all contact-message links from cache

		// Reset all previous message selections
		checkedmessages.each(function(message) {
			$('msg-' + message.key).setStyle("height:50px;");
			var target = $('msg-' + message.key).down('img');
			target.src = getstatesrc(0);
			if(hascomments) {
				target = target.next('a');
				target.show();
				//console.info(target);
				target.update('Remark');
			}
			target =  $('com-' + message.key);
			//target.hide();
			target = target.down('textarea');
			target.stopObserving();
			target.setStyle({color: "black"});
			$('com-' + message.key).hide();
			target.value = "";
			target = target.next('span');
			target.hide();
			checkedmessages.unset(message.key);
		});

		if(contactsize == 1) {
			checkedcontacts.each(function(contact) {
				var messages = checkedcache.get(category).get(contact.key)
				if(messages != undefined) {
					messages.each(function(msg) {
						var target = $('msg-' + msg.key).down('img');
						target.src = getstatesrc(2);
						if(hascomments && msg.value != "") {
							target = $('com-' + msg.key);
							target.down('textarea').value = msg.value;
						} else {
							$('msg-' + msg.key).setStyle('height:50px;');
						}
						checkedmessages.set(msg.key,2);
					});
				}
			});
			return;
		}


		var selectedmessages = new Hash();
		var selectedcomments = new Hash();
		var newcontct = false;
		checkedcontacts.each(function(contact) {
			var messages = checkedcache.get(category).get(contact.key)
			if(messages != undefined) {
				messages.each(function(msg) {
					var count = selectedmessages.get(msg.key) || 0;
					count++;
					selectedmessages.set(msg.key,count);

					var comment = selectedcomments.get(msg.key) || false;
					
					if((msg.value != "" && msg.value === comment) || (comment === false && count == 1)) {
						selectedcomments.set(msg.key,msg.value);
					} else if((comment !== false && msg.value !== comment) || (comment === false && count > 1)) {
						selectedcomments.set(msg.key,true);
					}
				});
			} else {
				newcontct = true;
			}
		});

		// Set all contact-message link boxes
		selectedmessages.each(function(message) {
			if(message.value == contactsize) {
				$('msg-' + message.key).down('img').src = getstatesrc(2);
				checkedmessages.set(message.key,2);
			} else {
				$('msg-' + message.key).down('img').src = getstatesrc(1);
				checkedmessages.set(message.key,1);
			}
			if(hascomments) {
				var comment = selectedcomments.get(message.key) || false;
				var textarea = $('com-' + message.key).down('textarea');
				if(comment === false) {
					textarea.value = "";
				} else if(comment === true || newcontct == true ) {
					textarea.value = "";
					// Add multiple remark notice
					blankFieldValue(textarea, "Multiple Remarks. Typing a new remark here will overwrite.");
					//textarea.next('span').show();
				} else {
					textarea.value = comment;
					//$('com-' + message.key).show();
				}
			}
			//$('com-' + message.key).hide();
		});
	}
	
	/*
	 * Get the class contacts and set observers
	 */
	function getclass(selected) {
		new Ajax.Request('classroommessage.php',
		{
			method:'get',
			parameters: {sectionid: selected},
			onSuccess: function(transport){
				var response = transport.responseJSON || "Class not available";
				$('contactbox').update("");

				$('theinstructions').show();
				$('tabsContainer').hide();
				$('searchContainer').hide();

				clearcache();

				revealmessages = true;

				var dom = $('contactbox').remove();
				var tbody = new Element('tbody');

				$H(response.people).each(function(person) {
					var id = 'c-' + person.key;
					var tr = new Element('tr');
					tr.insert('<td><a href="#" id="' + id + '" title="Student id: ' +  person.value.pkey + '" style="text-decoration:none;">' + person.value.name +'</a></td>');
					categoryinfo.each(function(category) {
						tr.insert('<td><img id="' + id + "-" + category.key + '"src="' + category.value.img + '" title="' + category.value.name + '" style="width:10px;display:none;" alt="" /></td>');
					});
					tbody.insert(tr);
				});
				dom.insert(new Element('table').insert(tbody));
				$('contactwrapper').insert(dom);


				$H(response.people).each(function(person) {
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
									$('c-' + contact.key).setStyle('font-weight:normal;');
									//$('i-c-' + contact.key).src = 'img/pixel.gif';
									checkedcontacts.unset(contact.key);
								});
							}

							// Select or deselect the itme depending on alt click. Unable to deselect if only one item is selected
							if (event.shiftKey && checkedcontacts.get(this.id.substr(2)) != undefined && checkedcontacts.size() > 1) {
								this.setStyle('font-weight:normal;');
								//$('i-' + this.id).src = 'img/pixel.gif';
								checkedcontacts.unset(this.id.substr(2));
							} else {
								this.setStyle('font-weight:bold');
								//$('i-' + this.id).src = h_image;
								checkedcontacts.set(this.id.substr(2),true);
							}
							// First click reveals the message board
							if(revealmessages) {
								revealmessages = false;
								$('theinstructions').hide();
								$('tabsContainer').show();
								$('searchContainer').show();
								var searchBox = $('searchbox');
								searchBox.focus();
								searchBox.blur();
							}

							// ==================================
							updatemessages(tabs.currentSection);
					});

					$(id).observe('mouseover', function(event) {
						this.style.background = c_hover;
						var contactid = this.id.substr(2);
						var currenttab = tabs.currentSection.substr(4);;

						if(checkedcache.get(currenttab).get(contactid) != undefined) {
							checkedcache.get(currenttab).get(contactid).each(function(message) {
								$('msg-' + message.key).style.background = c_hover;
								highlightedmessages.set(message.key,true);
							});
						}
						checkedcache.each(function (category) {
							if(currenttab != category.key && category.value.get(contactid) != undefined) {
								tabs.sections['lib-' + category.key].titleDiv.style.background = c_hover;
								tabs.sections['lib-' + category.key].titleDiv.down('img').pulsate({pulses:2, duration: 1.5});
							}
						});
					});
					$(id).observe('mouseout', function(event) {
						highlightedmessages.each(function(message) {
							$('msg-' + message.key).style.background = c_none;
							highlightedmessages.unset(message.key);
						});
						//if(checkedcontacts.get(this.title)){
							//this.style.background = c_selected;
						//} else
							this.style.background = c_none;


						categoryinfo.each(function (category) {
							tabs.sections['lib-' + category.key].titleDiv.style.background = '';
						});
					});
				});
				setcache(response.cache);
			}
		});
	}

document.observe("dom:loaded", function() {
	
	getclass($('classselect').getValue());

	$('picker').observe("selectstart", function(event) {          // disable select in IE
		if(!(event.target.hasClassName('targetcomment') || event.target.id == "searchbox"))
			event.stop();
		$('searchbox').blur();
	});
	$('picker').observe("mousedown", function(event) {			  // disable select in FF
		if(!(event.target.hasClassName('targetcomment') || event.target.id == "searchbox"))
			event.stop();
		$('searchbox').blur();
	});
	
	/*
	 * Static observers
	 */
	$('checkall').observe('click', function(event) {
		event.stop();
		$$('#contactbox a').each(function(contact) {
			//contact.style.background = "#ffcccc";
			contact.setStyle('font-weight:bold');
			$('i-' + contact.id).src = h_image;
			checkedcontacts.set(contact.id.substr(2),true);
		});
		if(revealmessages) {
			$('theinstructions').hide();
			revealmessages = false; 
			$('tabsContainer').show();
			$('searchContainer').show();

		}
		updatemessages(tabs.currentSection);
	}.bindAsEventListener($('contactbox')));

	$('classselect').observe('change', function(event) {
		event.stop();
		getclass(event.element().getValue());
	});

	$$('#libraryContent .targetmessage').each(function(message) {
		message.observe('click', function(event) {
			event.stop();

			var htmlid = this.id;  // html id: message-category-mid
			var msgid = this.id.substr(4);  // strip 'msg-'
			var state = checkedmessages.get(msgid) || 0;
			if(event.target.hasClassName('commentlink')) {
				$('msgtxt-' + msgid).setStyle('height:auto');
				event.target.hide();
				$(htmlid).setStyle('height:auto');	
				var target = $('com-' + msgid);
				target.toggle();
				target = target.down('textarea');
				target.focus();
				target.blur();
				if(state == 2) {
					return;
				}
			}
			// Don't modify anything if writing a comment'
			if(!event.target.hasClassName('targetcomment')) {
				
				state = (state == 2)? 0 : 2;

				var textarea = $('com-' + msgid).down('textarea');
				if((state == 0) && hascomments && textarea.getValue() != "") {
					if(!confirm('The custom remark will be removed if unselecting.'))
						return;
					else {
						textarea.value = "";
						textarea.stopObserving();
					}
				}



				//var text = ""; //event.target.down('textarea').getValue();
				// Save event to database
				new Ajax.Request('classroommessage.php',
				{
					method:'post',
					parameters: {eventContacts: checkedcontacts.keys().toJSON(),
								eventMessage: msgid,
								isChecked:(state == 2)},
					onFailure: function(){ alert('Unable to Set Message') },
					onException: function(){ alert('Unable to Set Message') },
					onSuccess: function(transport){
						$(htmlid).down('img').src = getstatesrc(state);
						checkedmessages.set(msgid,state);                  // Set Message to appropriate state
						// Set each selected contact to
						checkedcontacts.each(function(contact) {
							if(state == 2) {
								highlightedcontacts.set('c-' + contact.key,true);
								//$('i-c-' + contact.key).src = h_image;
								$('c-' + contact.key).style.background =  c_hover;
							} else {
								highlightedcontacts.unset('c-' + contact.key);
								//$('i-c-' + contact.key).src = 'img/pixel.gif';
								$('c-' + contact.key).style.background =  c_none;
								$(htmlid).setStyle('height:50px');
								$('msgtxt-' + msgid).setStyle('height:35px');

								$(htmlid).down('span').hide();
								$(htmlid).down('a').show();
							}
							setEvent(contact.key,msgid,(state == 2),"");
						});
					}
				});
			}
		});

		message.observe('mouseover', function(event) {
			event.stop();
			var htmlid = this.id;
			var msgid = this.id.substr(4);  // strip 'msg-'

			$(htmlid).style.background = c_hover;
			checkedcache.get(tabs.currentSection.substr(4)).each(function(contact) {
				if(contact.value.get(msgid) != undefined) {
					//$('i-c-' + contact.key).src = h_image;
					$('c-' + contact.key).style.background =  c_hover;
					highlightedcontacts.set('c-' + contact.key,true);
				}
			});

		});

		message.observe('mouseout', function(event) {
			event.stop();
			$(this.id).style.background = c_none;
			highlightedcontacts.each(function(contact) {
				//$('i-' + contact.key).src = 'img/pixel.gif';
				$(contact.key).style.background =  c_none;
				highlightedcontacts.unset(contact.key);
			});
		});
	});


	// Load tabs
	tabs = new Tabs('tabsContainer',{});

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
	tabs.show_section('lib-' + categoryinfo.keys().first());

	tabs.container.observe('Tabs:ClickTitle', function(event) {
		updatemessages(event.memo.section);
	});
	var searchBox = $('searchbox');
	blankFieldValue(searchBox, "Search Comments");
});



</script>
<?
include_once("navbottom.inc.php");
?>