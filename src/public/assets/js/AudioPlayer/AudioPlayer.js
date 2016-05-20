window.AudioPlayer = AudioPlayer;

function AudioPlayer(options) {

	var driver;
	var ui;

	initialize(options.el, options.drivers);

	this.load = function(files) {
		if (! driver) { return; }
		driver.load(files);
		ui.start();
	};

	this.unload = function() {
		ui.stop();
	};

	function initialize(el, drivers) {
		var driverContainer = document.createElement('div');
		var uiContainer = document.createElement('div');

		driver = instantiateSupportedDriver(drivers, driverContainer);

		if (!driver) { return; }

		// clear the unsupported driver message if present.
		el.innerHTML = "";

		ui = new AudioPlayer.UI(uiContainer, driver);

		el.appendChild(uiContainer);
		el.appendChild(driverContainer);
	}

	function instantiateSupportedDriver(drivers, container) {
		var DriverClass;

		for (var i = 0; i < drivers.length; i++) {
			DriverClass = AudioPlayer.Drivers[drivers[i].name];

			if (DriverClass.isSupported()) {
				drivers[i].container = container;
				return new DriverClass(drivers[i]);
			}
		}

		return undefined;
	}
}

AudioPlayer.Drivers = {};
