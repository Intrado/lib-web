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
	$classpeople[$i] = array(0 => "Ben Hencke - ". $period , 1 => "Howard Wood - ". $period,2 => "Gretel Baumgartner - ". $period, 3 => "Kee-Yip Chan - ". $period, 4 => "Nickolas Heckman - ". $period);
}

$categories = "{Positive: 'img/icons/award_star_gold_2.gif',Corrective: 'img/icons/lightning.gif',Informational: 'img/icons/information.gif'}";


$library = array("Positive" => array(),"Corrective" => array(),"Informational" => array());
foreach($library as $title => $messages) {
	for($i=0;$i<30;$i++) {
		$library[$title][$i] = $title . ' Generic targeted student message ' . $i;
	}
}


if (isset($_GET['classid'])) {
	header('Content-Type: application/json');
	$id = $_GET['classid'] + 0;
	if(isset($classpeople[$id])){
		echo json_encode($classpeople[$id]);
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




function contactbox($values) {
	$height = "450px";
	$style = isset($height) ? ('style="height: ' . $height . '; overflow: auto;"') : '';
	$n = "contactbox";
	$str = '<a id="checkall" href="#" style="float:left; white-space: nowrap;">Check All</a><br />';
	$str .= '<div id='.$n.' class="radiobox" '.$style.'>';
	$counter = 1;
	foreach ($values as $checkvalue => $checkname) {
		$id = $n.'-'.$counter;
		$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" style="display:none;" /><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><label></label><img src="img/icons/fugue/tick.gif" style="display:none" alt=""></img><br />
				';
		$counter++;
	}
	$str .= '</div>';
	return $str;
}

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
			<?= contactbox($classpeople[1]); ?>
			<hr />
			<img src="img/icons/fugue/light_bulb.gif" alt="" />Press shift key to multiselect
			<hr />
		</td>

		<td style="vertical-align:top;">
			<div id="theinstructions" style="font-size:2em;padding:100px;"><img src="img/icons/fugue/arrow_180.png" alt="" style="vertical-align:middle;"/>&nbsp;Click on a Contact to Start</div>

			<div id='tabsContainer' style=' margin:10px; margin-right:0px;display:none;vertical-align:middle'></div>
			<? foreach($library as $title => $messages) {
					// add library to id since user may change the title of the category
					echo "<div id='" . $title . "-library' style='display:block;'>
							";
					$n = 'm_';
					$nn = 'mm_';
					$count = 0;
					foreach($messages as $id => $message) {
						echo '<div id="'.$nn.$id.$title.'" class="targetmessage" style="border:dashed 1px silver;background-color:#FFF;width:300px;float:left;margin:10px;")"><img src="img/checkbox-clear.png" alt="" style="vertical-align:middle;"/>&nbsp;<label id="'.$n.$id.$title.'-label">' . $message .  ' </label><div style="display:none;">Comment </div></div>';
					}

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



	var checkedcache = new Hash();			// History of Contact to Message links
	
	var checkedcontacts = new Hash();		// List of the Contacts that are currently checked
	var checkedmessages = new Hash();		// List of Messages that are currently checked
	var highlightedmessages = new Hash();	// List of Messages that are currently highlighted
	var highlightedcontacts = new Hash();	// List of Contacts that are currently highlighted
	var lastmessagehover = "none";


	var revealmessages = true;			// Boolean to reveal messages on first click



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
		checkedcache = new Hash();
	}
	function setlink(contact,message,state) {
		if(checkedcache.get(contact) == undefined)
			checkedcache.set(contact,new Hash());
		if(state)
			checkedcache.get(contact).set(message,true);
		else
			checkedcache.get(contact).unset(message);
	}
	function haslink(contact,message) {
		if(checkedcache.get(contact) == undefined || checkedcache.get(contact).get(message) == undefined)
			return false;
		return true;
	}

	function updatemessages() {
		var contactsize = checkedcontacts.size();
		var selectedmessages = new Hash();

		// Get all contact-message links from cache
		checkedcontacts.each(function(contact) {
			if(checkedcache.get(contact.key) != undefined) {
				checkedcache.get(contact.key).each(function(msg) {
					var count = selectedmessages.get(msg.key) | 0;
					count++;
					selectedmessages.set(msg.key,count);
				});
			}
		});

		// Reset all previous message selections
		checkedmessages.each(function(message) {
			$(message.key).down('img').src = getstatesrc(0);
			checkedmessages.unset(message.key);
		});

		// Set all contact-message link boxes
		selectedmessages.each(function(message) {
			if(message.value == contactsize) {
				$(message.key).down('img').src = getstatesrc(2);
				checkedmessages.set(message.key,2);
			} else {
				$(message.key).down('img').src = getstatesrc(1);
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
			method:'get',
			parameters: {classid: selected},
			onSuccess: function(transport){
				var response = transport.responseJSON || "Class not available";
				$('contactbox').update("");
				$('theinstructions').show();
				$('tabsContainer').hide();

				//$('themessages').fade({ duration: 0.5 });
				//setTimeout("$('theinstructions').show()",1000);
				revealmessages = true;
				checkedcontacts = new Hash();

				var counter = 0;
				response.each(function(person) {
					var id = 'contactbox-' + counter;
					counter++;
					$('contactbox').insert('<input id="' + id + '" name="contactbox[]" type="checkbox" value="' + person + '" style="display:none;" /><label id="' + id + '-label" for="' + id + '">' + person +'</label><label></label><img src="img/icons/fugue/tick.gif" style="display:none" alt=""></img><br />');

					/*
					 * Observe Contact Click. Select one contact at a time or multiple contacts with
					 * alt key pressed.
					 */
					$(id + '-label').observe('click', function(event) {
							event.stop();
							//$(this).focus();


							if(!event.shiftKey) {
								checkedcontacts.each(function(contact) {
									$(contact.key).style.background = c_none;
									if(contact.value) {
										$(contact.key).previous().checked = false;
									}
									checkedcontacts.unset(contact.key);
								});
							}

							// Select or deselect the itme depending on alt click. Unable to deselect if only one item is selected
							if (event.shiftKey && this.previous().checked == true && checkedcontacts.size() > 1) {
								checkedcontacts.unset(this.id);
								$(this.id).style.background = c_none;
								this.previous().checked = false;
								
							} else {
								this.style.background = c_selected;
								checkedcontacts.set(this.id,true);
								this.previous().checked = true;
							}

							//console.info(revealmessages);
							// First click reveals the message board
							if(revealmessages) {
								revealmessages = false;

								$('theinstructions').hide();
								$('tabsContainer').show();
								//revealmessages = 1;
								//new Effect.Opacity('themessages', {from: 0.0,to: 1.0,duration: 1.0});
								//$('themessages').appear();
								//$('themessages').appear({ duration: 1.0 });
								//$('theinstructions').hide();
								//setTimeout("$('theinstructions').hide();$('themessages').show();", 1000);
							}

							// ==================================
							updatemessages();
					});

					$(id + '-label').observe('mouseover', function(event) {
						event.stop();
						this.style.background = c_hover;

						if(checkedcache.get(this.id) == undefined) {
							return;
						} else {
							checkedcache.get(this.id).each(function(message) {
								//console.info("set" + message.key);
								$(message.key).style.background = c_hover;
								highlightedmessages.set(message.key,true)
							});
						}
					});
					$(id + '-label').observe('mouseout', function(event) {
						event.stop();
						highlightedmessages.each(function(message) {
							//console.info("unset" + message.key);
							$(message.key).style.background = c_none;
							highlightedmessages.unset(message.key);
						});
						if(this.previous('input').checked)
							this.style.background = c_selected;
						else
							this.style.background = c_none;
					});
				});

				//console.info($('contactbox'));
			},
			onFailure: function(){ alert('Could not get class') }
		});
		clearcache();
	}


document.observe("dom:loaded", function() {
	$('classselect').setValue(0);
	getclass(0);

	$('picker').observe("selectstart", function(event) {          // disable select in IE
		event.stop();
	});
	$('picker').observe("mousedown", function(event) {			  // disable select in FF
		event.stop();
	});


	/*
	 * Static observers
	 */
	$('checkall').observe('click', function(event) {
		event.stop();
		var checkboxes = this.select('input');
		var count = checkboxes.length;
		for (var i = 0; i < count; ++i) {
			checkboxes[i].checked = true;
			var label = checkboxes[i].next('label');
			label.style.background = "#ffcccc";
			checkedcontacts.set(label.id,true);
		}
		if(revealmessages) {
			$('theinstructions').hide();
			revealmessages = false
			$('tabsContainer').show();


			//new Effect.Opacity('themessages', {from: 0.0,to: 1.0,duration: 1.0});
			//$('themessages').appear();
		}


		updatemessages();
	}.bindAsEventListener($('contactbox')));

	$('classselect').observe('change', function(event) {
		event.stop();
		getclass(event.element().getValue());
	});

	var categories = new Array('<?= implode(array_keys($library),"','")?>');


	categories.each(function(category) {

		$$('#' + category + '-library .targetmessage').each(function(message) {
			message.observe('click', function(event) {
				msgid = this.id;
				var state = checkedmessages.get(msgid) || 0;
				state = (state == 2)? 0 : 2;
				$(msgid).down('img').src = getstatesrc(state);
				checkedmessages.set(msgid,state);                  // Set Message to appropriate state
				// Set each selected contact to
				checkedcontacts.each(function(contact) {
					setlink(contact.key,msgid,(state == 2));
				});
			});
			message.observe('mouseover', function(event) {
				event.stop();
				msgid = this.id;
				$(msgid).style.background = c_hover;
				checkedcache.each(function(contact) {
					if(contact.value.get(msgid) === true) {
						$(contact.key).style.background = c_hover;
						highlightedcontacts.set(contact.key,true);
					}
				});
			});
			message.observe('mouseout', function(event) {
				event.stop();
				$(this.id).style.background = c_none;
				highlightedcontacts.each(function(contact) {
					if(checkedcontacts.get(contact.key) === true)
						$(contact.key).style.background = c_selected;
					else
						$(contact.key).style.background = c_none;
					highlightedcontacts.unset(contact.key);
				});
			});

		});
	});



	tabs = new Tabs('tabsContainer',{});





	var categoriesimages = new Hash(<?= $categories ?>);


	categories.each(function(category) {

		var image = "img/icons/bug.gif";
		if(categoriesimages.get(category))
			image = categoriesimages.get(category);
		tabs.add_section(category + '-library');
		tabs.update_section(category + '-library', {
			"title": category,
			"icon": image,
			"content": $(category + '-library').remove()
		});
	});

	tabs.show_section(categories.first() + '-library');

});



</script>
<?
include_once("navbottom.inc.php");
?>



