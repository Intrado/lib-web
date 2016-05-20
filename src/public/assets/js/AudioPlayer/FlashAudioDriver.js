
AudioPlayer.Drivers.Flash = FlashAudioDriver;

FlashAudioDriver.isSupported = function() {
	try {
		if (new ActiveXObject('ShockwaveFlash.ShockwaveFlash')) {
			return true;
		}
	} catch (e) {
		if (navigator.mimeTypes
				&& navigator.mimeTypes['application/x-shockwave-flash'] != undefined
				&& navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin) {
			return true;
		}
	}
	return false;
};

function FlashAudioDriver(options) {
	var flashObject;
	var niftyplayer = options.niftyplayer;
	var container = options.container;

	this.getState = function() {
		try {
			return {
				time: getTime(),
				duration: getDuration(),
				loading: isLoading(),
				playing: isPlaying(),
			};
		} catch (e) {
			return {
				time: 0,
				duration: 1,
				loaded: false,
				playing: false,
			};
		}
	};

	this.load = function(urls) {
		appendFlashElement(urls);
	};

	this.play = function() { runAction('play'); };

	this.stop = function() { runAction('stop'); };

	this.pause = function() { runAction('pause'); };

	function getTime() { return getVar('sP'); }
	function getDuration() { return getVar('sD'); }

	function isLoading() {
		return getVar('loadingState') !== 'loaded';
	}

	function isPlaying() {
		return getVar('playingState') === 'playing';
	}

	function getVar(varName) {
		return flashObject.GetVariable(varName);
	}

	function runAction(action) {
		flashObject.TCallLabel('/', action);
	}

	function appendFlashElement(files) {
		var src = generateFlashSrc(files);

		container.innerHTML = template.replace(/{{src}}/g, src);

  	if (navigator.appName.indexOf ("Microsoft") !=-1) {
			flashObject = container.getElementsByTagName('object')[0];
		} else {
			flashObject = container.getElementsByTagName('embed')[0];
		}
	}

	function generateFlashSrc(files) {
		for (var i = 0; i < files.length; i++) {
			files[i] = files[i] + "?cachebust=" + Date.now();
		}
		var fileParam = encodeURIComponent(files.join("|"));

		return niftyplayer + "?file=" + fileParam;
	}

	var template = "<object " +
"classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'" +
"width='0'" +
"align=''" +
"height='0'>" +
"<PARAM NAME='_cx' VALUE='4365'>" +
"<PARAM NAME='_cy' VALUE='1005'>" +
"<PARAM NAME='FlashVars' VALUE=''>" +
"<PARAM NAME='Movie' VALUE='{{src}}'>" +
"<PARAM NAME='Src' VALUE='{{src}}'>" +
"<PARAM NAME='WMode' VALUE='Window'>" +
"<PARAM NAME='Play' VALUE='0'>" +
"<PARAM NAME='Loop' VALUE='-1'>" +
"<PARAM NAME='Quality' VALUE='High'>" +
"<PARAM NAME='SAlign' VALUE='LT'>" +
"<PARAM NAME='Menu' VALUE='-1'>" +
"<PARAM NAME='Base' VALUE=''>" +
"<PARAM NAME='AllowScriptAccess' VALUE=''>" +
"<PARAM NAME='Scale' VALUE='NoScale'>" +
"<PARAM NAME='DeviceFont' VALUE='0'>" +
"<PARAM NAME='EmbedMovie' VALUE='0'>" +
"<PARAM NAME='BGColor' VALUE='FFFFFF'>" +
"<PARAM NAME='SWRemote' VALUE=''>" +
"<PARAM NAME='MovieData' VALUE=''>" +
"<PARAM NAME='SeamlessTabbing' VALUE='1'>" +
"<PARAM NAME='Profile' VALUE='0'>" +
"<PARAM NAME='ProfileAddress' VALUE=''>" +
"<PARAM NAME='ProfilePort' VALUE='0'>" +
"<PARAM NAME='AllowNetworking' VALUE='all'>" +
"<PARAM NAME='AllowFullScreen' VALUE='false'>" +
"<PARAM NAME='AllowFullScreenInteractive' VALUE='false'>" +
"<PARAM NAME='IsDependent' VALUE='0'>" +
"<PARAM NAME='BrowserZoom' VALUE='scale'>" +

"<param name='movie' value=''>" +

"<param name='quality' value='high'>" +

"<param name='bgcolor' value='#FFFFFF'>" +

"<embed height='0' type='application/x-shockwave-flash' align='' pluginspage='//get.adobe.com/flashplayer' width='0' src='{{src}}' embed-src='{{src}}' quality='high' bgcolor='#FFFFFF'>" +
"		</object>";

}
