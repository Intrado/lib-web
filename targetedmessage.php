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

// data Generating test date
$classes = array();
$classpeople = array();
for($i=0;$i<=3;$i++) {
	$period = $i + 1;
	$classes[$i] = "History Class -- Period " . $period;
	$classpeople[$i] = array("p_0001" => "Ben Hencke", "p_0002" => "Howard Wood","p_0003" => "Gretel Baumgartner", "p_0004" => "Kee-Yip Chan", "p_0005" => "Nickolas Heckman");
}



$categoriesjson = "{1: {name:'Positive',img:'img/icons/award_star_gold_2.gif'},2: {name: 'Corrective',img: 'img/icons/lightning.gif'},3: {name:'Informational',img: 'img/icons/information.gif'}}";
$categoriesimg = "{1: 'img/icons/award_star_gold_2.gif',2: 'img/icons/lightning.gif',3: 'img/icons/information.gif'}";

$categories = array(1 => "Positive",2 => "Corrective",3 => "Informational");

// category id => personid => messageid;
$library = array(1 => array(),2 => array(),3 => array());
$msgcount = 0;
foreach($library as $title => $messages) {
	for($i=0;$i<30;$i++) {
		$library[$title][$msgcount] = $title . ' Generic targeted student message ' . $i . ' 012346789 01234567890 1234567890 123456789';
		$msgcount++;
	}
}

//Handle ajax request. when swithcing sections
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

			<div id="contactwrapper">
				<div id="contactbox" style="width:100%;text-decoration:none;"></div>
			</div>
			<hr />
			<img src="img/icons/fugue/light_bulb.gif" alt="" />Press shift key to multiselect
			<hr />
		</td>

		<td style="vertical-align:top;">
			<div id="theinstructions" style="font-size:2em;padding:100px;"><img src="img/icons/fugue/arrow_180.png" alt="" style="vertical-align:middle;"/>&nbsp;Click on a Contact to Start</div>

			<div id='tabsContainer' style=' margin:10px; margin-right:0px;display:none;vertical-align:middle;'></div>

			<div id="libraryContent">
			<?
				$libraryids = array();
				$messageids = array();
				foreach($library as $categoryid => $messages) {
					// add library to id since user may change the title of the category
					echo "<div id='lib-$categoryid' style='display:block;'>";
					foreach($messages as $messageid => $message) {
						echo '<div id="msg-' . $messageid.'" class="targetmessage" style="border:solid 1px silver;background-color:#FFF;width:300px;float:left;margin:10px;")"><img src="img/checkbox-clear.png" alt="" style="position:relative;top:10px;left:3px;"/>&nbsp;<div style="position:relative;top:-10px;left:20px;width:270px;border:1px dashed silver;">' . $message .  ' </div><a href="#" class="commentlink" style="displayblock;float:right;">Comment </a>
							<textarea class="targetcomment" style="display:none;position:relative;clear:both;width:94%;left:2%;height:60px;border:1px solid red;background:white;"></textarea>
						</div>';

						//$messageids[] = "'lib-$librarycount$messagecount':$id";
						//$messagecount++;
					}
					//$libraryids[] = "'lib-$librarycount':'$title'";
					echo '<div style="clear:both;"></div></div>';
				}
			?>
			</div>
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

	/*
	 Checked cache contains
	 Checkedcache -> Category -> Contact -> Message

	 ie. Checkedcache -> "Positive" -> "p_0001" -> "m_0001"

	 Checkedcache has an extra level Category to easaly determine if a contact has one or more messages checked in under one category
	*/
	var checkedcache = new Hash();			// History of Contact to Message links


	var categoryinfo = $H(<?= $categoriesjson ?>);

	var checkedcontacts = new Hash();		// List of the Contacts that are currently checked
	var checkedmessages = new Hash();		// List of Messages that are currently checked
	var highlightedmessages = new Hash();	// List of Messages that are currently highlighted
	var highlightedcontacts = new Hash();	// List of Contacts that are currently highlighted

	var revealmessages = true;			// Boolean to reveal messages on first click

	var tabs;
	
	var categoriesimages = new Hash(<?= $categoriesimg ?>);


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
			checkedcache.set(category.value,new Hash());
		});
	}
	/*
	 * setEvent
	 * Takes a contact by its pid.
	 * Takes a message by its mid.
	 *
	 * The HTML ids for the are concatinated with 'contactbox-' for a contact and something else for message
	 */

	function setEvent(contactid,messageid,isChecked,comment) {
		var category = tabs.currentSection.substr(4); // strip 'lib-'
		var people = checkedcache.get(category);
		if(people.get(contactid) == undefined)
			people.set(contactid,new Hash());
		var contactlink = people.get(contactid);
		if(isChecked) {
			var img = $('contactbox-' + contactid).next('img');
			while(img != undefined && img.name != category) {
				img = img.next('img');
			}
			if(img != undefined) {
				img.show();
			}
			contactlink.set(messageid,comment);
		} else {
			contactlink.unset(messageid);
			if(contactlink.size() == 0) {
				var img = $('contactbox-' + contactid).next('img');
				while(img.name != undefined && img.name != category) {
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

	function updatemessages(currenttab) {
		var contactsize = checkedcontacts.size();
		var selectedmessages = new Hash();
		var category = currenttab.substr(4);
		// Get all contact-message links from cache
		checkedcontacts.each(function(contact) {
			var messages = checkedcache.get(category).get(contact.key)
			if(messages != undefined) {
				messages.each(function(msg) {
					var count = selectedmessages.get(msg.key) | 0;
					count++;
					selectedmessages.set(msg.key,count);
				});
			}
		});


		// Reset all previous message selections
		checkedmessages.each(function(message) {
			var target = $('msg-' + message.key).down('img');
			target.src = getstatesrc(0);
			target.next('textarea').value = "";
			target.next('textarea').hide();
			target.next('a').update('Comment');
			checkedmessages.unset(message.key);
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

			/*	var icons = "";
				librarymap.each(function(category) {
					var image = "img/icons/bug.gif";
					if(categoriesimages.get(category.value))
						image = categoriesimages.get(category.value);
					icons += '<img src="' + image + '" name="' + category.value + '" class="' + category.value + '-library"style="width:10px;display:none;" alt="" />';
				});
				*/
				//$('themessages').fade({ duration: 0.5 });
				//setTimeout("$('theinstructions').show()",1000);
				revealmessages = true;
				checkedcontacts = new Hash();

				for(var person in response){
					var id = 'contactbox-' + person;

					var dom = $('contactbox').remove();
					dom.insert('<img src="img/pixel.gif" style="width:10px;height:10px;vertical-align:middle;" alt="" / ><a href="#" id="' + id + '" title="' + person + '" style="text-decoration:none;">' + response[person] +'</a><br/>');
					$('contactwrapper').insert(dom);

					/*
					 * Observe Contact Click. Select one contact at a time or multiple contacts with
					 * alt key pressed.
					 */
					$(id).observe('click', function(event) {
							event.stop(); // Some browsers may open another winbdow on shift click
							if(!event.shiftKey) {
								checkedcontacts.each(function(contact) {
									$('contactbox-' + contact.key).style.background = c_none;
									checkedcontacts.unset(contact.key);
								});
							}

							// Select or deselect the itme depending on alt click. Unable to deselect if only one item is selected
							if (event.shiftKey && this.previous().checked == true && checkedcontacts.size() > 1) {
								checkedcontacts.unset(this.id.substr(11));
								$(this.id).style.background = c_none;
							} else {
								this.style.background = c_selected;
								checkedcontacts.set(this.id.substr(11),true);
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
						this.style.background = c_hover;
						var contactid = this.id.substr(11);
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
						if(checkedcontacts.get(this.title))
							this.style.background = c_selected;
						else
							this.style.background = c_none;


						categoryinfo.each(function (category) {
							tabs.sections['lib-' + category.key].titleDiv.style.background = '';
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
		$$('#contactbox a').each(function(contact) {
			contact.style.background = "#ffcccc";
			checkedcontacts.set(contact.id.substr(11),true);
		});
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

	$$('#libraryContent .targetmessage').each(function(message) {
		message.observe('click', function(event) {
			event.stop();

			var htmlid = this.id;  // html id: message-category-mid
			var msgid = this.id.substr(4);  // strip 'msg-'

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
								setEvent(contact.key,target.up().id.substr(4),true,target.getValue());
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
					setEvent(contact.key,msgid,(state == 2),"");
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
					$('contactbox-' + contact.key).previous('img').src = h_image;
					highlightedcontacts.set('contactbox-' + contact.key,true);
				}
			});
		});

		message.observe('mouseout', function(event) {
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

});



</script>
<?
include_once("navbottom.inc.php");
?>