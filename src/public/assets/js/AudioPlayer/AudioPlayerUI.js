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

		draw();
	}

	function updateState() {
		state = driver.getState();
	}

	function drawTime() {
		var timeS = state.time / 1000;

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
