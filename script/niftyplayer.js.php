<?
//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>
// borrowed and modified from http://www.kirupa.com/developer/mx/detection.htm
var hasflash = false;
var contentVersion = 8;
var plugin = (navigator.mimeTypes && navigator.mimeTypes["application/x-shockwave-flash"]) ? navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin : 0;
if (plugin) {
	var words = navigator.plugins["Shockwave Flash"].description.split(" ");
	for (var i = 0; i < words.length; ++i) {
		if (isNaN(parseInt(words[i])))
			continue;
		var pluginVersion = words[i]; 
	}
	hasflash = pluginVersion >= contentVersion;
} else if (navigator.userAgent && navigator.userAgent.indexOf("MSIE")>=0 && (navigator.appVersion.indexOf("Win") != -1)) {
	document.write('<SCR' + 'IPT LANGUAGE=VBScript\> \n'); //FS hide this from IE4.5 Mac by splitting the tag
	document.write('on error resume next \n');
	document.write('hasflash = ( IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash." & contentVersion)))\n');
	document.write('</SCR' + 'IPT\> \n');
}


// Script for NiftyPlayer 1.7, by tvst from varal.org
// Released under the MIT License: http://www.opensource.org/licenses/mit-license.php

var FlashHelper =
{
	movieIsLoaded : function (theMovie)
	{
		if (typeof(theMovie) != undefined){
			return theMovie.PercentLoaded() == 100;
		} else 
			return false;
  },

	getMovie : function (movieName)
	{
		return (navigator.appName.indexOf ("Microsoft") !=-1 && typeof(window[movieName]) != 'undefined')?
				window[movieName]:document[movieName];
	}
};

function niftyplayer(name)
{
	this.obj = FlashHelper.getMovie(name);

	if (!FlashHelper.movieIsLoaded(this.obj)) return;

	this.play = function () {
		this.obj.TCallLabel('/','play');
	};

	this.stop = function () {
		this.obj.TCallLabel('/','stop');
	};

	this.pause = function () {
		this.obj.TCallLabel('/','pause');
	};

	this.playToggle = function () {
		this.obj.TCallLabel('/','playToggle');
	};

	this.reset = function () {
		this.obj.TCallLabel('/','reset');
	};

	this.load = function (url) {
		this.obj.SetVariable('currentSong', url);
		this.obj.TCallLabel('/','load');
	};

	this.loadAndPlay = function (url) {
		this.load(url);
		this.play();
	};

	this.getState = function () {
		var ps = this.obj.GetVariable('playingState');
		var ls = this.obj.GetVariable('loadingState');

		// returns
		//   'empty' if no file is loaded
		//   'loading' if file is loading
		//   'playing' if user has pressed play AND file has loaded
		//   'stopped' if not empty and file is stopped
		//   'paused' if file is paused
		//   'finished' if file has finished playing
		//   'error' if an error occurred
		if (ps == 'playing')
			if (ls == 'loaded') return ps;
			else return ls;

		if (ps == 'stopped')
			if (ls == 'empty') return ls;
			if (ls == 'error') return ls;
			else return ps;

		return ps;

	};

	this.getPlayingState = function () {
		// returns 'playing', 'paused', 'stopped' or 'finished'
		return this.obj.GetVariable('playingState');
	};

	this.getLoadingState = function () {
		// returns 'empty', 'loading', 'loaded' or 'error'
		return this.obj.GetVariable('loadingState');
	};

	this.registerEvent = function (eventName, action) {
		// eventName is a string with one of the following values: onPlay, onStop, onPause, onError, onSongOver, onBufferingComplete, onBufferingStarted
		// action is a string with the javascript code to run.
		//
		// example: niftyplayer('niftyPlayer1').registerEvent('onPlay', 'alert("playing!")');

		this.obj.SetVariable(eventName, action);
	};

	return this;
}


/* Reliance Communications 
 * Embed the NiftyPlayer and load audio if flash player was embedded sucessfully. Otherwise use the native mp3 player.
 */

function embedPlayer(url,target,parts) {
	if(hasflash) {
		var requestfiles = url;
		if(typeof(parts) != "undefined") {
			if(parts > 1) {
				var tmp = [];
				tmp.push(url + '&partnum=1');
				var urlpart = '|'+ url + '&partnum=';
				for(var i=2; i <= parts;i++) {
					tmp.push(i);
				}
				requestfiles = tmp.join(urlpart);
			} else {
				requestfiles = url + '&partnum=1';
			}
		} 
		$(target).update('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="<?= isset($_SERVER['HTTPS'])?"https":"http" ?>://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="165" height="38" id="niftyPlayer1" align="">' +
			'<param name=movie value="media/niftyplayer.swf?file=' + encodeURIComponent(requestfiles)  + '&as=1">' + 
			'<param name=quality value=high>' + 
			'<param name=bgcolor value=#FFFFFF>' + 
			'<embed src="media/niftyplayer.swf?file=' + encodeURIComponent(requestfiles) + '&as=1" quality=high bgcolor=#FFFFFF width="165" height="38" name="niftyPlayer1" align="" type="application/x-shockwave-flash" pluginspage="<?= isset($_SERVER['HTTPS'])?"https":"http" ?>://get.adobe.com/flashplayer">' +
			'</embed>' + 
			'</object>');
	} else {
<?
		$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
		if($android) {
?>
			$(target).update("Unable to play message. Please install Flash for Android 2.2 or higher or click the link to download the message.");
<?		} else { ?>
			$(target).update("<object classid='clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95' width='280' height='45'>"+
				"<param name='type' value='audio/mpeg'>"+
				"<param name='src' value='" + url + "'>"+
				"<param name='autostart' value='1'>"+
				"<param name='showcontrols' value='1'>"+
				"<param name='showstatusbar' value='0'>"+
				"<embed src ='" + url + "' type='audio/mpeg' autoplay='true' autostart='1' width='280' height='45' controller='1' showstatusbar='0' bgcolor='#ffffff'></embed>"+ 
				"</object><br /><a href='http://get.adobe.com/flashplayer/'>Click here to install or upgrade your Flash&reg; player</a>");
<?		} ?>
	}
}





