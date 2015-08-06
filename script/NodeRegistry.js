NodeRegistry = function () {

	// private
	var nodes = {};
	
	// semi-private
	var Node = function (obj) {
		this.obj = obj;
		this.events = {};

		// Add a named event handler to this node
		//
		// The named event must be handled by a function; either a function is supplied, or
		// the NAME of a function in obj is supplied, or the event handler will be undefined.
		//
		// @param name string name of the event that we want to handle
		// @param handler mixed callable handler function OR name of a method under obj to use
		this.setEventHandler = function (name, handler) {
			if (typeof handler === 'function') {
				this.events[name] = handler;
			}
			else if (typeof this.obj[handler] === 'function') {
				this.events[name] = this.obj[handler];
			}
			return this;
		};

		// Handle the named event
		//
		// @param name String name of the event to handle
		//
		// Returns true if this node has an event handler for this name and has invoked it,
		// else false.
		this.handleEvent = function (name) {
			var res = true;
			if (typeof this.events[name] !== 'function') {
				res = false;
			}
			else {
				this.events[name]();
			}
		};
	};

	// public
	var NodeRegistry = {

		makeNode: function (obj) {
			return new Node(obj);
		},

		addNode: function (id, node) {
			if (NodeRegistry.hasNode(id)) return false;
			nodes[id] = node;
			return node;
		},

		getNode: function (id) {
			return NodeRegistry.hasNode(id) ? nodes[id] : null;
		},

		hasNode: function (id) {
			return nodes[id] ? true : false;
		},

		removeNode: function (id) {
			if (NodeRegistry.hasNode(id)) return;
			delete nodes[id];
		},

		// Fire the named event
		//
		// @param name String name of the event to handle
		// @param id String id of a specific node we want to fire the event on (optional; use null when skipping)
		//
		// If a node id is supplied, just fire the event on that one, otherwise iterate over
		// all nodes and fire the event on each one.
		fireEvent: function (name, id) {
			if (id) {
				if (NodeRegistry.hasNode(id)) {
					NodeRegistry.getNode(id).handleEvent(name);
				}
			}
			else {
				for (var nodeId in nodes) {
					NodeRegistry.getNode(nodeId).handleEvent(name);
				}
			}
		}
	};

	return NodeRegistry;
}();

