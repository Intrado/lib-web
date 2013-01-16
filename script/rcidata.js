
/**
 * Small object class to collect global reference data; this lets us pass data
 * from PHP into JavaScript and let arbitrary JavaScript gain access to it
 * based on name/value pairs. The general form of value allows it to be any
 * data type, including complete objects.
 *
 * Created by SMK 2013-01-03
 */
if (typeof RCIData === 'undefined') {
	RCIData = function() {
		this.data = Array();

		/**
		 * Set a data node with the specified name to the supplied value
		 */
		this.set = function(name, value) {
			this.data[name] = value;
		}

		/**
		 * Get the set value for the data node with the specified name
		 *
		 * @return mixed value stored for named data node, or boolean false if not set
		 */
		this.get = function(name) {
			if (! this.data[name]) {
				return(false);
			}

			return(this.data[name]);
		}

		/**
		 * Unset the value for the data node with the specified name
		 *
		 * @return boolean true on success, else false (if it was not set)
		 */
		this.unset = function(name) {
			if (! this.data[name]) {
				return(false);
			}

			delete(this.data[name]);
			return(true);
		}
	}

	rcidata = new RCIData();

	/*
	rcidata.set('test', '123');
	data = rcidata.get('test');
	console.log('data value is: [' + data + ']');
	*/
}

