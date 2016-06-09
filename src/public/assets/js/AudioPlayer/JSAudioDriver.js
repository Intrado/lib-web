AudioPlayer.Drivers.JS = JSAudioDriver;

JSAudioDriver.isSupported = function() {
	return !! (window.AudioContext || window.webkitAudioContext);
};

function JSAudioDriver(options) {
	var self = this;
	self.options = options || {};

	self.ac = new ( window.AudioContext || webkitAudioContext )();
	self.bufferList = [];
	self.sourceList = [];

	this.load = function (urls) {
		self.loading = true;

		var loader = new AsyncMultiLoader(urls, loadingComplete);

		urls.forEach(function(url, index) {
			var request = new XMLHttpRequest();
			request.open("GET", url, true);
			request.responseType = "arraybuffer";

			request.onload = function() {
				if (request.status > 399 || request.status < 200) {
					return loader.fail(index, 'MP3 fetch failed for ' + url);
				}

				self.ac.decodeAudioData(request.response, function(buffer) {
					loader.complete(index, buffer);
				});
			};

			request.onerror = function() {
				loader.fail(index, 'MP3 fetch failed for ' + url);
			};

			request.send();
		});
	};

	this.play = function(position) {
		connect();
		self.position = typeof position === 'number' ? position : self.position || 0;
		self.startTime = self.ac.currentTime - self.position;

		self.bufferList.forEach(function(buffer, index) {
			var effectiveStartTime = self.startTime + buffer.startTime;

			if ((self.position >= buffer.startTime) && (self.position < buffer.endTime)) {
				self.sourceList[index].source.start(
					effectiveStartTime > 0 ? effectiveStartTime : 0,
					self.position - buffer.startTime,
					buffer.duration
				);
			}

			if (self.position < buffer.startTime) {
				self.sourceList[index].source.start(
					effectiveStartTime > 0 ? effectiveStartTime : 0,
					0,
					buffer.duration
				);
			}
		});

		self.playing = true;
	};

	this.pause = function() {
		stopBuffers();
		self.position = self.ac.currentTime - self.startTime;
		self.playing = false;
	};

	this.stop = function() {
		stopBuffers();
		self.position = 0;
		self.playing = false;

		updatePosition();
	};

	this.getState = function() {
		try {
			updatePosition();

			return {
				time: self.position ? self.position * 1000 : 0,
				duration: self.totalDuration * 1000,
				loading: self.loading,
				playing: self.playing
			};
		} catch (e) {
			return {
				time: 0,
				duration: 1,
				loaded: false,
				playing: false
			};
		}
	};

	this.seek = function(timeMs) {
		self.position = timeMs / 1000;
	};

	function updatePosition() {
		self.position = self.playing ? self.ac.currentTime - self.startTime : self.position;

		if ( self.position >= self.totalDuration ) {
			self.stop();
		}
	};

	function stopBuffers() {
		self.bufferList.forEach(function(buffer, index) {
			if ( self.sourceList[index].source ) {
				try {
					// trying to stop a buffer before it's started playing
					// throws an error, so stopping and nullifying in TC
					self.sourceList[index].source.stop(0);
				} catch (e) {}

				self.sourceList[index].source = null;
			}
		});
	}

	function connect() {
		if ( self.playing ) {
			self.pause();
		}

		updateSourceList();
	};

	function updateSourceList() {
		self.bufferList.forEach(function(buffer, index) {
			self.sourceList[index] = {source: self.ac.createBufferSource()};
			self.sourceList[index].source.buffer = buffer;
			self.sourceList[index].source.connect(self.ac.destination);
		});
	}

	function loadingComplete(buffers) {
		if (!buffers) return;

		self.totalDuration = 0;

		buffers.forEach(function(buffer, index) {
			var previousBuffer;

			self.bufferList[index] = buffer;
			self.totalDuration += buffer.duration;

			if (index === 0) {
				buffer.startTime = 0;
				buffer.endTime = buffer.duration;
			} else {
				previousBuffer = self.bufferList[index - 1];
				buffer.startTime = previousBuffer.endTime;
				buffer.endTime = buffer.startTime + buffer.duration;
			}
		});

		// ensure sourceList buffers are ready once loaded
		// in case users try scrubbing before clicking Play button
		updateSourceList();

		self.loading = false;

	};

	function AsyncMultiLoader(urls, callback) {
		var buffers = [];
		var loadedBuffers = 0;

		this.complete = function(index, buffer) {
			buffers[index] = buffer;
			loadedBuffers++;
			if (loadedBuffers === urls.length) { callback(buffers); }
		};

		this.fail = function(index, message) { callback(message); };
	};

}
