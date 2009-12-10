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
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

// data
$classes = array();
$classpeople = array();
for($i=0;$i<=3;$i++) {
	$period = $i + 1;
	$classes[$i] = "History Class -- Period " . $period;
	$classpeople[$i] = array("p_0001" => "Ben Hencke", "p_0002" => "Howard Wood","p_0003" => "Gretel Baumgartner", "p_0004" => "Kee-Yip Chan", "p_0005" => "Nickolas Heckman");
}

$categories = "{Positive: 'img/icons/award_star_gold_2.gif',Corrective: 'img/icons/lightning.gif',Informational: 'img/icons/information.gif'}";

$library = array("Positive" => array(),"Corrective" => array(),"Informational" => array());
foreach($library as $title => $messages) {
	for($i=0;$i<30;$i++) {
		$library[$title][$i] = $title . ' Generic targeted student message ' . $i . ' 012346789 01234567890 1234567890 123456789';
	}
}

if (isset($_POST['classid'])) {
	header('Content-Type: application/json');
	$id = $_POST['classid'] + 0;
	if(isset($classpeople[$id])){
		echo json_encode($classpeople[$id]);
	}

	if(isset($_POST['cache'])) {
		error_log($_POST['cache']);
		$cache = json_decode($_POST['cache'],true);
		foreach($cache as $category => $contacts) {
			foreach($contacts as $contact => $messages) {
				error_log($contact);
				foreach($messages as $message => $comment) {
					error_log("    $message -- Comment: $comment");
				}
			}
		}
	}

	exit(0);
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function classselect($values) {
	$n = 'classselect';
	$value = 3;
	$str = '<select id='.$n.' name="'.$n.'">';
	foreach ($values as $selectvalue => $selectname) {
		$checked = $value == $selectvalue;
		$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>';
	}
	$str .= '</select>';
	return $str;
}

function fmt_template ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Classroom Message');

include_once("nav.inc.php");

startWindow(_L('Classroom Message'));
?>



<table width="100%" id="picker">
	<tr>
		<td style="top:0px;width:250px;vertical-align:top;border-right:1px solid black;padding-right:10px;">
			<?= classselect($classes); ?>
			<hr />
			<a id="checkall" href="#" style="float:left; white-space: nowrap;">Check All</a><br />
			<ul id="contactbox" style="list-style-type:none;width:100%;text-decoration:none;"><li>
				</li></ul>
			<hr />
			<img src="img/icons/fugue/light_bulb.gif" alt="" />Press shift key to multiselect
			<hr />
		</td>

		<td style="vertical-align:top;">
			<div id="theinstructions" style="font-size:2em;padding:100px;"><img src="img/icons/fugue/arrow_180.png" alt="" style="vertical-align:middle;"/>&nbsp;Click on a Contact to Start</div>

			<div id='tabsContainer' style=' margin:10px; margin-right:0px;display:none;vertical-align:middle;'></div>

			
			<?
				$libraryids = array();
				$messageids = array();
				$librarycount = 0;
				foreach($library as $title => $messages) {
					// add library to id since user may change the title of the category
					echo "<div id='lib-$librarycount' style='display:block;'>";
					$messagecount = 0;
					foreach($messages as $id => $message) {
						echo '<div id="lib-' . $librarycount.$messagecount.'" class="targetmessage" style="border:solid 1px silver;background-color:#FFF;width:300px;float:left;margin:10px;")"><img src="img/checkbox-clear.png" alt="" style="position:relative;top:10px;left:3px;"/>&nbsp;<div style="position:relative;top:-10px;left:20px;width:270px;border:1px dashed silver;">' . $message .  ' </div><a href="#" class="commentlink" style="displayblock;float:right;">Comment </a>
							<textarea class="targetcomment"  style="display:none;position:relative;clear:both;width:94%;left:2%;height:60px;border:1px solid red;background:white;"></textarea>
						</div>';

						$messageids[] = "'lib-$librarycount$messagecount':$id";
						$messagecount++;
					}
					$libraryids[] = "'lib-$librarycount':'$title'";
					$librarycount++;
					echo '<div style="clear:both;"></div></div>';
				}
			?>
		</td>
	</tr>
</table>


<?
endWindow();
?>
<script type="text/javascript" src="script/accordion.js"></script>
<script type="text/javascript" language="javascript">

	// Color variables
	var c_hover = "#bbcccc";
	var c_selected = "#ffcccc";
	var c_none = "#ffffff";
	var h_image = "img/icons/fugue/arrow.gif";





	var checkedcache = new Hash();			// History of Contact to Message links

	/*
	 Checked cache contains
	 Checkedcache -> Category -> Contact -> Message

	 ie. Checkedcache -> "Positive" -> "p_0001" -> "m_0001"

	 Checkedcache has an extra level Category to easaly determine if a contact has one or more messages checked in under one category
	*/
	var contactmap = new Hash(); // Link html ids to contact ids

	// Link html ids to contact ids
	var messagemap = new Hash({<?= implode($messageids,",")?>});
	var librarymap = new Hash({<?= implode($libraryids,",")?>});


	var checkedcontacts = new Hash();		// List of the Contacts that are currently checked
	var checkedmessages = new Hash();		// List of Messages that are currently checked
	var highlightedmessages = new Hash();	// List of Messages that are currently highlighted
	var highlightedcontacts = new Hash();	// List of Contacts that are currently highlighted
	var lastmessagehover = "none";

	var revealmessages = true;			// Boolean to reveal messages on first click

	var tabs;
	
	var categoriesimages = new Hash(<?= $categories ?>);


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
		librarymap.each(function(category) {
			checkedcache.set(category.value,new Hash());
			//category.value = new Hash(); // TODO look into JavaScript garbage collection.
		});
	}
	/*
	 * setlink
	 * Takes a contact by its pid.
	 * Takes a message by its mid.
	 *
	 * The HTML ids for the are concatinated with 'contactbox-' for a contact and something else for message
	 */
	function setlink(contact,message,state,comment) {
		var currenttab = librarymap.get(tabs.currentSection);
		var sectioncache = checkedcache.get(currenttab);
		if(sectioncache.get(contact) == undefined)
			sectioncache.set(contact,new Hash());
		var contactlink = sectioncache.get(contact);
		if(state) {
			var img = $('contactbox-' + contact).next('img');
			while(img != undefined && img.name != librarymap.get(tabs.currentSection)) {
				img = img.next('img');
			}
			if(img != undefined) {
				img.show();
			}
			contactlink.set(message,comment);
		} else {
			contactlink.unset(message);
			if(contactlink.size() == 0) {
				var img = $('contactbox-' + contact).next('img');
				while(img.name != undefined && img.name != librarymap.get(tabs.currentSection)) {
					img = img.next('img');
				}
				if(img.name != undefined) {
					img.hide();
				}
			}
		}
	}

	// has link in this section only
	function haslink(contact,message) {
		if(checkedcache.get(tabs.currentSection).get(contact) == undefined || checkedcache.get(tabs.currentSection).get(contact).get(message) == undefined)
			return false;
		return true;
	}

	function updatemessages(section) {
		var contactsize = checkedcontacts.size();
		var selectedmessages = new Hash();
		var currenttab = librarymap.get(section);


		// Get all contact-message links from cache
		checkedcontacts.each(function(contact) {
			if(checkedcache.get(currenttab).get(contact.key) != undefined) {
				checkedcache.get(currenttab).get(contact.key).each(function(msg) {
					var count = selectedmessages.get(msg.key) | 0;
					count++;
					selectedmessages.set(msg.key,count);
				});
			}
		});

		// Reset all previous message selections
		checkedmessages.each(function(message) {
			$(tabs.currentSection + message.key).down('img').src = getstatesrc(0);
			checkedmessages.unset(message.key);
		});

		// Set all contact-message link boxes
		selectedmessages.each(function(message) {
			if(message.value == contactsize) {
				$(tabs.currentSection + message.key).down('img').src = getstatesrc(2);
				checkedmessages.set(message.key,2);
			} else {
				$(tabs.currentSection + message.key).down('img').src = getstatesrc(1);
				checkedmessages.set(message.key,1);
			}
		});
	}
	
	/*
	 * Get the class contacts and set observers
	 */
	function getclass(selected) {
		new Ajax.Request('targetedmessage.php',
		{
			method:'post',
			parameters: {classid: selected,cache: checkedcache.toJSON()},
			onSuccess: function(transport){
				var response = transport.responseJSON || "Class not available";
				$('contactbox').update("");

				$('theinstructions').show();
				$('tabsContainer').hide();
				clearcache();

				var icons = "";
				librarymap.each(function(category) {
					var image = "img/icons/bug.gif";
					if(categoriesimages.get(category.value))
						image = categoriesimages.get(category.value);

					icons += '<img src="' + image + '" name="' + category.value + '" class="' + category.value + '-library"style="width:10px;display:none;" alt="" />';
				});

				//$('themessages').fade({ duration: 0.5 });
				//setTimeout("$('theinstructions').show()",1000);
				revealmessages = true;
				checkedcontacts = new Hash();
				contactmap = new Hash();

				var counter = 0;
				for(var person in response){
					var id = 'contactbox-' + person;
					counter++;
					contactmap.set(id,person);
					$('contactbox').insert('<li><img src="img/pixel.gif" style="width:10px;height:10px;vertical-align:middle;" alt="" / ><a href="#" id="' + id + '" title="' + person + '" style="text-decoration:none;">' + response[person] +'</a>' + icons + '</li>');

					/*
					 * Observe Contact Click. Select one contact at a time or multiple contacts with
					 * alt key pressed.
					 */
					$(id).observe('click', function(event) {
							event.stop();
							if(!event.shiftKey) {
								checkedcontacts.each(function(contact) {
									$('contactbox-' + contact.key).style.background = c_none;
									checkedcontacts.unset(contact.key);
								});
							}

							// Select or deselect the itme depending on alt click. Unable to deselect if only one item is selected
							if (event.shiftKey && this.previous().checked == true && checkedcontacts.size() > 1) {
								checkedcontacts.unset(contactmap.get(this.id));
								$(this.id).style.background = c_none;
							} else {
								this.style.background = c_selected;
								checkedcontacts.set(contactmap.get(this.id),true);
							}

							// First click reveals the message board
							if(revealmessages) {
								revealmessages = false;
								$('theinstructions').hide();
								$('tabsContainer').show();
							}

							// ==================================
							updatemessages(tabs.currentSection);
					});

					$(id).observe('mouseover', function(event) {
						event.stop();
						this.style.background = c_hover;
						var contactid = contactmap.get(this.id);
						var currenttab = librarymap.get(tabs.currentSection);


						if(checkedcache.get(currenttab).get(contactid) != undefined) {
							checkedcache.get(currenttab).get(contactid).each(function(message) {
								$(tabs.currentSection + message.key).style.background = c_hover;
								highlightedmessages.set(tabs.currentSection + message.key,true);
							});
						}
						librarymap.each(function (category) {
							if(currenttab != category.value  && checkedcache.get(currenttab).get(contactid) != undefined) {
								tabs.sections[category.key].titleDiv.style.background = c_hover;

								//tabs.sections[category.key].titleDiv.pulsate({pulses:2, duration: 1.5});
							}
						});
					});
					$(id).observe('mouseout', function(event) {
						event.stop();
						highlightedmessages.each(function(message) {
							$(message.key).style.background = c_none;
							highlightedmessages.unset(message.key);
						});
						if(checkedcontacts.get(this.title))
							this.style.background = c_selected;
						else
							this.style.background = c_none;


						librarymap.each(function (category) {
							tabs.sections[category.key].titleDiv.style.background = '';
						});
					});
				}

			},
			onFailure: function(){ alert('Could not get class') }
		});




	}

document.observe("dom:loaded", function() {
	$('classselect').setValue(0);
	getclass(0);

	$('picker').observe("selectstart", function(event) {          // disable select in IE
		if(!event.target.hasClassName('targetcomment'))
			event.stop();
	});
	$('picker').observe("mousedown", function(event) {			  // disable select in FF
		if(!event.target.hasClassName('targetcomment'))
			event.stop();
	});
	
	/*
	 * Static observers
	 */
	$('checkall').observe('click', function(event) {
		event.stop();
		contactmap.each(function(contact) {
			$(contact.key).style.background = "#ffcccc";
			checkedcontacts.set(contact.value,true);
		})
		if(revealmessages) {
			$('theinstructions').hide();
			revealmessages = false; 
			$('tabsContainer').show();
		}
		updatemessages(tabs.currentSection);
	}.bindAsEventListener($('contactbox')));

	$('classselect').observe('change', function(event) {
		event.stop();
		getclass(event.element().getValue());
	});

	messagemap.each(function(message) {
		$(message.key).observe('click', function(event) {
			event.stop();

			var htmlid = this.id;  // html id: message-category-mid
			var msgid = messagemap.get(this.id);  // message id as in db:  mid

			var state = checkedmessages.get(msgid) || 0;
			if(event.target.hasClassName('commentlink')) {
				if(event.target.next('textarea').visible()){
					event.target.update("Comment");
					event.target.next('textarea').stopObserving('keyup');
				} else {
					event.target.update("Close");
					event.target.next('textarea').observe('keydown',function(e){
						var keyunicode=e.charCode || e.keyCode;
						if (keyunicode == 13) {
							var target = e.element();
							checkedcontacts.each(function(contact) {
								setlink(contact.key,messagemap.get(target.up().id),true,target.getValue());
							});
							e.stop();
						}
					});
				}
				event.target.next('textarea').toggle();
				if(state == 2) {
					return;
				}
			}
			// Don't modify anything if writing a comment'
			if(!event.target.hasClassName('targetcomment')) {
				state = (state == 2)? 0 : 2;
				$(htmlid).down('img').src = getstatesrc(state);
				checkedmessages.set(msgid,state);                  // Set Message to appropriate state
				// Set each selected contact to
				checkedcontacts.each(function(contact) {
					if(state == 2) {
						highlightedcontacts.set('contactbox-' + contact.key,true);
						$('contactbox-' + contact.key).previous('img').src = h_image;
					} else {
						highlightedcontacts.unset('contactbox-' + contact.key);
						$('contactbox-' + contact.key).previous('img').src = "img/pixel.gif";
						$(htmlid).down('textarea').hide();
						$(htmlid).down('a').update("Comment");
					}
					setlink(contact.key,msgid,(state == 2),"");
				});
			}
		});

		$(message.key).observe('mouseover', function(event) {
			event.stop();
			var htmlid = this.id;
			var msgid = messagemap.get(this.id);;
			var currenttab = librarymap.get(tabs.currentSection);

			$(htmlid).style.background = c_hover;
			checkedcache.get(currenttab).each(function(contact) {
				if(contact.value.get(msgid) != undefined) {
					$('contactbox-' + contact.key).previous('img').src = h_image;
					highlightedcontacts.set('contactbox-' + contact.key,true);
				}
			});
		});

		$(message.key).observe('mouseout', function(event) {
			event.stop();
			$(this.id).style.background = c_none;
			highlightedcontacts.each(function(contact) {
				$(contact.key).previous('img').src = "img/pixel.gif";
				highlightedcontacts.unset(contact.key);
			});
		});
	});


	// Load tabs
	tabs = new Tabs('tabsContainer',{});

	librarymap.each(function(category) {
		checkedcache.set(category.value,new Hash());
		var image = "img/icons/bug.gif";
		if(categoriesimages.get(category.value))
			image = categoriesimages.get(category.value);
		tabs.add_section(category.key);
		tabs.update_section(category.key, {
			"title": category.value,
			"icon": image,
			"content": $(category.key).remove()
		});
	});
	tabs.show_section(librarymap.keys().first());

	tabs.container.observe('Tabs:ClickTitle', function(event) {
		updatemessages(event.memo.section);
	});

});



</script>
<?
include_once("navbottom.inc.php");
?>