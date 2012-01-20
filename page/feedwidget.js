
var feeddata = null;
var vars = new Array();

function getVars() {
	// pull the vars from the get request in the url.
	var queryvars = window.location.search.substring(1).split("&");
	for (var i = 0; i < queryvars.length; i++) {
		var p = queryvars[i].split("=");
		vars[p[0]] = unescape(p[1]);
	}
}

function genFeed() {
	if ((feeddata.readyState === 4) || (feeddata.readyState === "complete")) {
		// create the feed div in the body
		var feeddiv = document.createElement("div");
		feeddiv.setAttribute("style", vars.box);
		document.getElementsByTagName('body')[0].appendChild(feeddiv);
		
		// get the rss xml
		var feedxml = feeddata.responseXML.getElementsByTagName("rss")[0];

		// find the main title and add it to the document
		var feedtitle = document.createElement("h2");
		feedtitle.setAttribute("style", vars.head);
		var title = document.createTextNode(feedxml.getElementsByTagName("title")[0].childNodes[0].nodeValue);
		
		// get/set the link (if there is one)
		var feedlink = feedxml.getElementsByTagName("link")[0];
		if (feedlink != undefined && feedlink.firstChild) {
			var href = document.createElement("a");
			href.setAttribute("href",feedlink.firstChild.nodeValue);
			href.setAttribute("target","_blank");
			href.setAttribute("style","color:inherit;");
			href.appendChild(title)
			feedtitle.appendChild(href);
		} else {
			feedtitle.appendChild(title);
		}
		
		feeddiv.appendChild(feedtitle);
		
		// put the feed items in a list
		var feedul = document.createElement("ul");
		feedul.setAttribute("style", vars.list);
		feeddiv.appendChild(feedul);
		
		var feeditems = feedxml.getElementsByTagName('item');
		var feeditemlink;
		var feeditemmediagroup;
		var feeditemmedia;
		var feeditemdescription;
		var descdiv;
		var itemtitle;
		var itemlabel;
		var linkhref;
		var mediadiv = null;
		var mediahref;
		var mediabutton;
		var swfurl;
		var mp3url;
		var itemdiscription;
		for (var i = 0; i < feeditems.length; i++) {
			// get the label for the item
			itemtitle = document.createTextNode(feeditems[i].getElementsByTagName("title")[0].firstChild.nodeValue);
			// create the description
			descdiv = document.createElement("div");
			descdiv.setAttribute("style",vars.desc);
			descdiv.appendChild(document.createTextNode(feeditems[i].getElementsByTagName("description")[0].firstChild.nodeValue));
			// add link if there is one
			feeditemlink = feeditems[i].getElementsByTagName("link")[0];
			if (feeditemlink != undefined && feeditemlink.firstChild) {
				// add a link to the label
				itemlabel = document.createElement("a");
				itemlabel.setAttribute("href",feeditemlink.firstChild.nodeValue);
				itemlabel.setAttribute("target","_blank");
				itemlabel.setAttribute("style","color:inherit;");
				itemlabel.appendChild(itemtitle);
				
				// add a link to the description
				linkhref = document.createElement("a");
				linkhref.setAttribute("href",feeditemlink.firstChild.nodeValue);
				linkhref.setAttribute("target","_blank");
				linkhref.appendChild(document.createTextNode("Details..."));
				descdiv.appendChild(document.createTextNode("  "));
				descdiv.appendChild(linkhref);
			} else {
				itemlabel = itemtitle;
			}
			// add a list item
			itemli = document.createElement("li");
			itemli.appendChild(itemlabel);
			itemli.appendChild(descdiv);
			
			// find the media items
			feeditemmediagroup = feeditems[i].getElementsByTagName("media:group")[0];
			if (feeditemmediagroup == undefined) // WebKit, Gecko
				feeditemmediagroup = feeditems[i].getElementsByTagName("group")[0];
			if (feeditemmediagroup) {
				feeditemmedia = feeditemmediagroup.getElementsByTagName("media:content");
				if (feeditemmedia[0] == undefined) // WebKit, Gecko
					feeditemmedia = feeditemmediagroup.getElementsByTagName("content");

				mediadiv = document.createElement("div");
				// get the swf and mp3 urls
				swfurl = null;
				mp3url = null;
				for (var m = 0; m < feeditemmedia.length; m++) {
					if (feeditemmedia[m].attributes.getNamedItem("type").value == "audio/mpeg")
						mp3url = feeditemmedia[m].attributes.getNamedItem("url").value;
					else if (feeditemmedia[m].attributes.getNamedItem("type").value == "application/x-shockwave-flash")
						swfurl = feeditemmedia[m].attributes.getNamedItem("url").value + "&as=1";
				}
				
				if (swfurl || mp3url ) {
					descdiv.appendChild(mediadiv);
					// create a clickable to insert the player (IE7 won't evaluate onClick if you use js to insert the clickable, cause it's dumb)
					mediadiv.innerHTML = '<span style="'+vars.audio+'" onClick="insertPlayerObject(this.parentNode,\''+swfurl+'\',\''+mp3url+'\')"><img src="img/nifty_play.png" /> Play audio</span>';
				}
			}
			feedul.appendChild(itemli);
			
		}
	}
}

function getFeedXml(onready) {
	feeddata = null;
	if (window.XMLHttpRequest) { // XMLHttpRequest object for IE7+, OP, FF, etc...
		feeddata = new XMLHttpRequest();
	} else if ( window.ActiveXObject ) { // AtiveXObject for older IE browsers
		try {
			feeddata = new ActiveXObject("Microsoft.XMLHTTP");
		} catch( e ) {
			feeddata = new ActiveXObject("Msxml2.XMLHTTP");
		}
	}
	if (feeddata !== null) {
		feeddata.onreadystatechange = onready;
		feeddata.open("GET", vars.feedurl, true);
		// sending out request
		feeddata.send();
	} else {
		// TODO: uh oh... maybe generate a href?
		alert("broswer couldn't create a request object");
	}
}

// insert html representing the object necessary for media playback (IE doesn't let you insert objects with js for some stupid reason)
function insertPlayerObject(container,swfurl,mp3url) {
	if (hasflash && swfurl) {
		container.innerHTML = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="165" height="38"><param name="movie" value="'+swfurl+'" /><object type="application/x-shockwave-flash" data="'+swfurl+'" width="165" height="38"><param name="movie" value="'+swfurl+'"/></object></object>';
	} else if (mp3url) {
		container.innerHTML = '<object classid="clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95" width="165" height="45"><param name="type" value="audio/mpeg" /><param name="src" value="'+mp3url+'" /><param name="autostart" value="true" /><object type="audio/mpeg" data="'+mp3url+'" width="165" height="45" autoplay="false"></object></object>';
	}
}

window.onload = function() { 
	getVars();
	getFeedXml(genFeed);
};
