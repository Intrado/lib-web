AudioPlayer.UI = AudioPlayerUI;

function AudioPlayerUI(el, driver) {

	var playing, loading, scrubbing, durationMs, timeMs, running, ui;

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

		ui = {};
		playing = false;
		loading = true;
		scrubbing = false;
		durationMs = 1;
		timeMs = 0;
		running = false;

		addClass('audio-player');

		ui.track = div(el, 'track');
		ui.progress = div(ui.track, 'progress');
		ui.scrubber = div(ui.track, 'scrubber');

		ui.time = div(el, 'time');
		ui.timeMinutes = span(ui.time, 'time-minutes');
		ui.time.appendChild(document.createTextNode(':'));
		ui.timeSeconds = span(ui.time, 'time-seconds');

		ui.controls = div(el, 'controls');
		ui.playButton = div(ui.controls, 'button-play fa fa-play');
		ui.pauseButton = div(ui.controls, 'button-pause fa fa-pause');
		ui.loader = div(ui.controls, 'loader fa fa-spinner fa-spin');
		ui.stopButton = div(ui.controls, 'button-stop fa fa-stop');

		ui.playButton.addEventListener('click', driver.play);
		ui.stopButton.addEventListener('click', driver.stop);
		ui.pauseButton.addEventListener('click', driver.pause);

		if (driver.seek) { bindScrubEvents(); }

		draw();
	}

	function updateState() {
		var driverState = driver.getState();
		playing = driverState.playing;
		loading = driverState.loading;
		durationMs = driverState.duration;

		// If we're currently scrubbing, we don't want to use the driver's
		// current time.
		if (! scrubbing) { timeMs = driverState.time; }
	}

	function drawTime() {
		var timeS = timeMs / 1000;

		ui.timeMinutes.innerHTML = Math.floor(timeS / 60)

		var seconds = "" + Math.floor(timeS % 60);
		if (seconds.length === 1) {
			seconds	=	"0" + seconds;
		}

		ui.timeSeconds.innerHTML = seconds;
	}

	function drawTrack() {
		var trackWidth = ui.track.offsetWidth;
		var percentComplete = timeMs / durationMs * 100;

		ui.progress.style.width = "" + percentComplete + "%";
		ui.scrubber.style.left =  "" + percentComplete + "%";
	}

	function drawPlay() {
		if (loading) {
			ui.playButton.style.display = 'none';
			ui.pauseButton.style.display = 'none';
			ui.loader.style.display = 'inline-block';
			return;
		}

		ui.loader.style.display = 'none';

		if (playing) {
			ui.playButton.style.display = 'none';
			ui.pauseButton.style.display = 'inline-block';
		} else {
			ui.playButton.style.display = 'inline-block';
			ui.pauseButton.style.display = 'none';
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
		ui.scrubber.addEventListener('mousedown', onMouseDown);
		ui.scrubber.addEventListener('touchstart', onMouseDown);

		el.addEventListener('mousemove', onDrag);
		el.addEventListener('touchmove', onDrag);

		el.addEventListener('mouseup', onMouseUp);
		el.addEventListener('touchend', onMouseUp);
	};

	function onMouseDown( e ) {
		driver.pause();
		scrubbing = true;
	};

	function onDrag( e ) {
		var pageX, trackXOffset, trackX, progressRatio;

		if (! scrubbing) { return; }

		// Math to take the absolute position of the event on the page
		// and find out how far along the track that position is.

		pageX = e.pageX ? e.pageX : e.targetTouches[0].pageX;

		trackXOffset = absoluteXPosition(ui.track);

		trackX = pageX - trackXOffset;

		var progressRatio = clamp(0, 1, trackX / ui.track.offsetWidth);

		timeMs = progressRatio * durationMs;
	};

	function onMouseUp() {
		if (! scrubbing ) { return; }

		driver.seek(timeMs);

		scrubbing = false;
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

	function absoluteXPosition(el) {
		var scrollOffset = window.pageXOffset ||
			document.documentElement.scrollLeft ||
			document.body.scrollLeft;


		var documentOffset = document.documentElement.clientLeft ||
			document.body.clientLeft || 0;

		return el.getBoundingClientRect().left + scrollOffset - documentOffset;
	}

	// given a min, a max and a value, return either the value, the min or the max
	function clamp(min, max, x) {
		return Math.max(min, Math.min(max, x));
	}
}
