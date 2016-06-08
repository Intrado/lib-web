AudioPlayer.UI = AudioPlayerUI;

function AudioPlayerUI(el, driver) {
	var self = this;
	var state;
	var running;

	this.start = function() {
		running = true;
		tick();
	};

	this.stop = function() {
		running = false;
	};

	setup();

	function setup() {
		// This function sets up our ui elements and renders them
		// with the initial default state.

		state = {
			playing: false,
			loading: true,
			duration: 1,
			time: 0
		};

		addClass('audio-player');

		self.track = div(el, 'track');
		self.progress = div(self.track, 'progress');
		self.scrubber = div(self.track, 'scrubber');

		self.time = div(el, 'time');
		self.timeMinutes = span(self.time, 'time-minutes');
		self.time.appendChild(document.createTextNode(':'));
		self.timeSeconds = span(self.time, 'time-seconds');

		self.controls = div(el, 'controls');
		self.playButton = div(self.controls, 'button-play fa fa-play');
		self.pauseButton = div(self.controls, 'button-pause fa fa-pause');
		self.loader = div(self.controls, 'loader fa fa-spinner fa-spin');
		self.stopButton = div(self.controls, 'button-stop fa fa-stop');

		self.playButton.addEventListener('click', driver.play);
		self.stopButton.addEventListener('click', driver.stop);
		self.pauseButton.addEventListener('click', driver.pause);

		// check if driver supports scrubbing
		if (driver.scrubStop) { bindScrubEvents(); }

		draw();
	}

	function updateState() {
		state = driver.getState();
	}

	function drawTime(time) {
		var _time = time || state.time;
		var timeS = _time / 1000;

		self.timeMinutes.innerHTML = Math.floor(timeS / 60)

		var seconds = "" + Math.floor(timeS % 60);
		if (seconds.length === 1) {
			seconds	=	"0" + seconds;
		}

		self.timeSeconds.innerHTML = seconds;
	}

	function drawTrack() {
		var timeMs = state.time;
		var trackWidth = self.track.offsetWidth;
		var percentComplete = timeMs / state.duration * 100 % 100;

		self.progress.style.width = "" + percentComplete + "%";
		self.scrubber.style.left =  "" + percentComplete + "%";
	}

	function drawPlay() {
		if (state.loading) {
			self.playButton.style.display = 'none';
			self.pauseButton.style.display = 'none';
			self.loader.style.display = 'inline-block';
			return;
		}

		self.loader.style.display = 'none';

		if (state.playing) {
			self.playButton.style.display = 'none';
			self.pauseButton.style.display = 'inline-block';
		} else {
			self.playButton.style.display = 'inline-block';
			self.pauseButton.style.display = 'none';
		}
	}

	function draw() {
		drawTrack();
		drawTime();
		drawPlay();
	}

	function tick() {
		updateState();
		draw();
		if (running) { requestAnimationFrame(tick); }
	}

	function bindScrubEvents() {
		self.scrubber.addEventListener('mousedown', onMouseDown.bind(self));
		el.addEventListener('mousemove', onDrag.bind(self));
		el.addEventListener('mouseup', onMouseUp.bind(self));
	};

	function onMouseDown( e ) {
		self.dragging = true;
		self.startX = e.pageX;
		self.startLeft = (parseFloat(self.scrubber.style.left || 0) / 100) * self.track.offsetWidth;
	};

	function onDrag( e ) {
		var width, position, pwRatio, percentComplete;

		if (!self.dragging) {
			return;
		}
		// stop running so it doesn't interfere
		// with rendering the position of the scrubber
		running = false;

		// stop the driver's buffer(s) from playing
		driver.scrubStop();

		width = self.track.offsetWidth;
		position = self.startLeft + ( e.pageX - self.startX );
		position = Math.max(Math.min(width, position), 0);

		pwRatio = position / width;
		percentComplete = pwRatio * 100;

		self.progress.style.width = "" + percentComplete + "%";
		self.scrubber.style.left =  "" + percentComplete + "%";

		drawTime(pwRatio * driver.getState().duration);
	};

	function onMouseUp() {
		var width, left, time;

		if ( self.dragging ) {
			leftPositionPercent = parseFloat(self.scrubber.style.left || 0) / 100;
			time = leftPositionPercent * driver.getState().duration / 1000;
			driver.seek(time);

			self.dragging = false;
			running = true;
			requestAnimationFrame(tick);
		}
	};

	function requestAnimationFrame(callback) {
		if (window.requestAnimationFrame) {
			window.requestAnimationFrame(callback);
		} else {
			setTimeout(callback, 50);
		}
	}

	function addClass(classString) {
		if (el.classList) {
			el.classList.add(classString);
		} else {
			el.className += (' ' + classString);
		}
	}

	function div(root, className) {
		return element(root, 'div', className);
	}

	function span(root, className) {
		return element(root, 'span', className);
	}

	function element(root, name, className) {
		var el = document.createElement(name);
		el.className = className
		root.appendChild(el);
		return el;
	}
}
