
var feeddata;
var feedstyles;
var feedcategories;
var hasflash = true; // TODO: detect this somehow

function getVars() {
	// pull the vars from the get request in the url.
	feedvars = null;
	feedcategories = null;
	// split the vars off the end
	var attrib = window.location.search.split("vars=");
	if (attrib[1]) {
		feedstyles = JSON.parse(unescape(attrib[1]));
	}
	// next split off the categories
	if (attrib[0]) {
		var catattrib = attrib[0].split("c=");
		if (catattrib[1]) {
			feedcategories = catattrib[1];
		}
	}
	// TODO: get the custurl and the num items
}

function genFeed() {
	if ((feeddata.readyState === 4) || (feeddata.readyState === "complete")) {
		// create the feed div in the body
		var feeddiv = document.createElement("div");
		feeddiv.setAttribute("style", feedstyles.div);
		document.getElementsByTagName('body')[0].appendChild(feeddiv);
		
		// get the rss xml
		var feedxml = feeddata.responseXML.getElementsByTagName("rss")[0];
		
		// find the main title and add it to the document
		var feedtitle = document.createElement("h2");
		feedtitle.setAttribute("style", feedstyles.h2);
		feedtitle.appendChild(document.createTextNode(feedxml.getElementsByTagName("title")[0].childNodes[0].nodeValue));
		feeddiv.appendChild(feedtitle);
		
		// put the feed items in a list
		var feedul = document.createElement("ul");
		feedul.setAttribute("style", feedstyles.ul);
		feeddiv.appendChild(feedul);
		
		var feeditems = feedxml.getElementsByTagName('item');
		var itemli;
		var mediaspan = null;
		var feeditemmedia;
		var mediahref;
		var mediabutton;
		var swfurl;
		var mp3url;
		for (var i = 0; i < feeditems.length; i++) {
			itemli = document.createElement("li");
			itemli.appendChild(document.createTextNode(feeditems[i].getElementsByTagName("title")[0].firstChild.nodeValue));
			// find the media items
			feeditemmedia = feeditems[i].getElementsByTagName("media:content");
			mediaspan = document.createElement("span");
			for (var m = 0; m < feeditemmedia.length; m++) {
				itemli.appendChild(document.createElement("br"));
				itemli.appendChild(mediaspan);
				// create a button to insert the player (IE7 won't evaluate onClick if you use js to insert the button, cause it's dumb)
				// TODO: get real media url info 
				swfurl = "pp.swf?code=8&as=0&nump=1&bu=%2F";
				mp3url = "a.mp3.php?code=8&full&dl";
				mediaspan.innerHTML = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="text-decoration:underline;color:blue;cursor:pointer;" onClick="insertPlayerObject(this.parentNode,\''+swfurl+'\',\''+mp3url+'\')">Get Audio</span>';
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
		// TODO: real feed xml
		feeddata.open("GET", "testfeed.xml", true);
		// sending out request
		feeddata.send();
	} else {
		// TODO: uh oh... maybe generate a href?
		alert("broswer couldn't create a request object");
	}
}

// insert html representing the object necessary for media playback (IE doesn't let you insert objects with js for some stupid reason)
function insertPlayerObject(container,swfurl,mp3url) {
	if (hasflash) {
		container.innerHTML = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="165" height="38"><param name="movie" value="'+swfurl+'" /><object type="application/x-shockwave-flash" data="'+swfurl+'" width="165" height="38"><param name="movie" value="'+swfurl+'"/></object></object>';
	} else {
		container.innerHTML = '<object classid="clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95" width="165" height="45"><param name="type" value="audio/mpeg" /><param name="src" value="'+mp3url+'" /><param name="autostart" value="0" /><object type="audio/mpeg" data="'+mp3url+'" width="165" height="45" autoplay="false"></object></object>';
	}
}

window.onload = function() { 
	getVars();
	getFeedXml(genFeed);
};
