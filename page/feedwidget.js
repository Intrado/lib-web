
var feeddata = null;
var vars = new Array();

function getVars() {
	// pull the vars from the get request in the url.
	var queryvars = window.location.search.substring(1).split("&");
	for (var i = 0; i < queryvars.length; i++) {
		var p = queryvars[i].split("=");
		if (p[0] == "v")
			vars[p[0]] = JSON.parse(unescape(p[1]));
		else
			vars[p[0]] = unescape(p[1]);
	}
}

function genFeed() {
	if ((feeddata.readyState === 4) || (feeddata.readyState === "complete")) {
		// create the feed div in the body
		var feeddiv = document.createElement("div");
		feeddiv.setAttribute("style", vars.v.box);
		document.getElementsByTagName('body')[0].appendChild(feeddiv);
		
		// get the rss xml
		var feedxml = feeddata.responseXML.getElementsByTagName("rss")[0];
		
		// find the main title and add it to the document
		var feedtitle = document.createElement("h2");
		feedtitle.setAttribute("style", vars.v.head);
		feedtitle.appendChild(document.createTextNode(feedxml.getElementsByTagName("title")[0].childNodes[0].nodeValue));
		feeddiv.appendChild(feedtitle);
		
		// put the feed items in a list
		var feedul = document.createElement("ul");
		feedul.setAttribute("style", vars.v.list);
		feeddiv.appendChild(feedul);
		
		var feeditems = feedxml.getElementsByTagName('item');
		var feeditemmediagroup;
		var feeditemmedia;
		var feeditemdescription;
		var descdiv;
		var mediadiv = null;
		var mediahref;
		var mediabutton;
		var swfurl;
		var mp3url;
		var itemdiscription;
		for (var i = 0; i < feeditems.length; i++) {
			// add the label for the item
			itemli = document.createElement("li");
			itemli.appendChild(document.createTextNode(feeditems[i].getElementsByTagName("title")[0].firstChild.nodeValue));
			// insert the description
			descdiv = document.createElement("div");
			descdiv.setAttribute("style",vars.v.desc)
			descdiv.appendChild(document.createTextNode(feeditems[i].getElementsByTagName("description")[0].firstChild.nodeValue));
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
						swfurl = feeditemmedia[m].attributes.getNamedItem("url").value;
				}
				
				if (swfurl || mp3url ) {
					itemli.appendChild(mediadiv);
					// create a clickable to insert the player (IE7 won't evaluate onClick if you use js to insert the clickable, cause it's dumb)
					mediadiv.innerHTML = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="text-decoration:underline;color:blue;cursor:pointer;" onClick="insertPlayerObject(this.parentNode,\''+swfurl+'\',\''+mp3url+'\')">Get Audio</span>';
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
		feeddata.open("GET", "feed.php?cat="+vars.c+"&cust="+vars.cust+"&items="+((vars.i)?vars.i:10), false);
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
		container.innerHTML = '<object classid="clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95" width="165" height="45"><param name="type" value="audio/mpeg" /><param name="src" value="'+mp3url+'" /><param name="autostart" value="0" /><object type="audio/mpeg" data="'+mp3url+'" width="165" height="45" autoplay="false"></object></object>';
	}
}

window.onload = function() { 
	getVars();
	getFeedXml(genFeed);
};
