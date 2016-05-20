# AudioPlayer

The AudioPlayer object takes an element and a list of drivers. It selects the
first driver that is supported by the browser and initializes the UI.

If none of the given drivers are supported, it displays whatever content it
contains.

```
-- html
	<div id="player">Your browser is not supported.</div>
-- js
	var ap = new AudioPlayer({
		el: document.getElementById('player'),
		drivers: [ { name: 'flash', niftyplayer: 'niftyplayer.swf' } ]) });
	});

	ap.load([ 'files/1.mp3', 'files/2.mp3' ]);
```

* `load`: accepts a list of files, which will be loaded into the driver and made playable via the ui.



## AudioPlayerUI

The AudioPlayerUI object is responsible for rendering the player interface and
handling user events. It accepts a container element and a driver.

## Drivers

Methods:
* `load`: Takes a list of urls to load
* `play`: Starts playback
* `pause`: Stops playback, maintaining current position
* `stop`: Stops playback, moving current position to beginning
* `isSupported`: Returns whether the driver is supported in this browser
* `getState`: Returns an object with the following key/values:

  ```
{
	time: 0, // current position in millesconds
	duration: 64000, // total duration in milleseconds
	loading: false, // whether we are currently loading data
	playing: true, // whether we are currently playing
}
```
### Flash Driver

The flash driver utilizes niftyplayer.swf to play audio files. It requires flash
to be installed.

It accepts options:
```
{
	niftyplayer: '/path/to/niftyplayer.swf',
	container: // element to stick the flash object in
}
```

### Fake Driver

The fake driver plays no sound, just provides a driver for
testing.

It accepts options:
```
{
	duration: 60 * 1000 // the duration in ms the driver should have
	loadTime: 1 * 1000 // the amount of time in ms the driver should load
}
```

# Demo
```
cd demo
npm install
grunt
open localhost:9001
```
