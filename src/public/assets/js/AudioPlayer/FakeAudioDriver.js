AudioPlayer.Drivers.Fake = FakeAudioDriver;

FakeAudioDriver.isSupported = function() { return true; };

function FakeAudioDriver(options) {
	var self = this;
	var durationMs = 10 * 1000;
	var currentMs = 0;
	var startMs;
	var interval;
	var loaded = false;

	self.getState = function() {
		return {
			time: currentMs,
			duration: options.duration,
			loading: !loaded,
			playing: startMs,
		};
	};
	self.load = function() {
		setTimeout(function() {
			loaded = true;
		}, options.loadTime);
	};

	self.play = function() {
		startMs = Date.now() - currentMs;
		interval = setInterval(function() {
			currentMs = Date.now() - startMs;
		}, 10); //100hz
	};

	self.stop = function() {
		startMs = undefined;
		currentMs = 0;
		if(interval) { clearInterval(interval); interval = undefined; }
	};

	self.pause = function() {
		startMs = undefined;
		if(interval) { clearInterval(interval); interval = undefined; }
	};
}
