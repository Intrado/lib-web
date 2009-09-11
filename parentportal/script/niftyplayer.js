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
  	if (navigator.appName.indexOf ("Microsoft") !=-1) return window[movieName];
	  else return document[movieName];
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

function hasFlashPlayer(){	
	return (typeof(FlashHelper.getMovie('niftyPlayer1')) != 'undefined' && typeof(FlashHelper.getMovie('niftyPlayer1').PercentLoaded) != 'undefined');
}

function embedPlayer(url,target) { 
	if(hasFlashPlayer()){
		niftyplayer("niftyPlayer1").loadAndPlay(url);
	} else {
		$(target).update('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="165" height="38" id="niftyPlayer1" align="">' + 
		   '<param name=movie value="media/niftyplayer.swf?file=' + encodeURIComponent(url)  + '&as=1">' + 
		   '<param name=quality value=high>' + 
		   '<param name=bgcolor value=#FFFFFF>' + 
		   '<embed src="media/niftyplayer.swf?file=' + encodeURIComponent(url) + '&as=1" quality=high bgcolor=#FFFFFF width="165" height="38" name="niftyPlayer1" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">' + 
		   '</embed>' + 
		   '</object>');
	} 
	
	if(!hasFlashPlayer()) {
		   $(target).update("<object classid='clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95' width='280' height='45'>"+
			"<param name='type' value='audio/mpeg'>"+
			"<param name='src' value='" + url + "'>"+
			"<param name='autostart' value='1'>"+
			"<param name='showcontrols' value='1'>"+
			"<param name='showstatusbar' value='0'>"+
			"<embed src ='" + url + "' type='audio/mpeg' autoplay='true' autostart='1' width='280' height='45' controller='1' showstatusbar='0' bgcolor='#ffffff'></embed>"+ 
			"</object>");
   	}	   		
}


