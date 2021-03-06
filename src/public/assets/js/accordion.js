function make_split_pane(vertical, count) {
	var splitContainer = null;

	if (count === undefined)
		count = 2;

	if (vertical) {
		splitContainer = new Element('table', {'class':'SplitPane'});
		var tbody = new Element('tbody');
		var tr = new Element('tr');
		for (var i = 0; i < count; i++) {
			tr.insert(new Element('td', {'class':'SplitPane'}));
		}
		splitContainer.insert(tbody.insert(tr));
	} else {
		splitContainer = new Element('div');
		for (var i = 0; i < count; i++) {
			splitContainer.insert(new Element('div', {'class':'SplitPane'}));
		}
	}

	splitContainer.identify();
	return splitContainer;
}

/**
 * @author Kee-Yip Chan
 */
// Custom Events:
// container.fire("Accordion:ClickTitle", {section, currentSection}), if the event is not stopped then show the chosen section.
var Accordion = Class.create({
	// @param settings {hideDuration:0.3, showDuration:0.3}
	initialize: function(container, settings) {
		this.container = $(container);
		this.sections = {};

		this.settings = settings ? settings : {};
		if (this.settings.hideDuration === undefined)
			this.settings.hideDuration = 0.3;
		if (this.settings.showDuration === undefined)
			this.settings.showDuration = 0.3;
		if (this.settings.collapseCurrentSection === undefined)
			this.settings.collapseCurrentSection = true;
		this.currentSection = null;
		this.classPrefix = 'accordion';
		this.firePrefix = 'Accordion:';
	},
	get_section_containing: function(element) {
		element = $(element);
		for (var name in this.sections) {
			var section = this.sections[name];
			if (section.contentDiv.down('#' + element.identify()))
				return name;
		}
		return null;
	},
	
	add_section: function(name, disabled) {
		var section = new AccordionSection(name, disabled, this);
		this.container.insert(section.titleDiv).insert(section.contentDiv);
		this.sections[name] = section;
	},
	// @param options = {title:"", icon:"http://", content:Element or plain html}, not necessary to specify all options
	update_section: function(name, options) {
		this.sections[name].update(options);
	},
	lock_section: function(name) {
		this.sections[name].lock();
		this.sections[name].hide_content(true);
	},
	unlock_section: function(name) {
		this.sections[name].unlock();
	},
	enable_section: function(name) {
		this.sections[name].enable();
	},
	disable_section: function(name) {
		this.sections[name].disable();
	},
	collapse_all: function(ignoredSection, dontAnimate) {
		for (var name in this.sections) {
			if (name != ignoredSection)
				this.sections[name].hide_content(dontAnimate);
		}
		this.currentSection = null;
	},
	show_section: function(name, dontAnimate) {
		this.collapse_all(name);

		this.sections[name].show_content(dontAnimate);
		this.currentSection = name;
	},
	fire_event: function(action, data) {
		data.currentSection = this.currentSection;
		data.widget = this;

		var handledEvent = this.container.fire(this.firePrefix+action, data);
		if (action == 'ClickTitle' && !this.settings.collapseCurrentSection && data.section == this.currentSection) {
			handledEvent.stop();
		}
		return handledEvent;
	}
});

// Custom Events:
// container.fire("Tabs:ClickTitle", {section}), if the event is not stopped then show the chosen section.
// settings.vertical defaults to false.
// settings.collapseCurrentSection is ignored, always false.
var Tabs = Class.create(Accordion, {
	initialize: function($super, container, settings) {
		settings = settings || {};
		if (settings.hideDuration === undefined)
			settings.hideDuration = 0.1;
		if (settings.showDuration === undefined)
			settings.showDuration = 0.1;
		if (settings.vertical === undefined)
			settings.vertical = false;
		settings.collapseCurrentSection = false;
		$super(container, settings);

		if (this.settings.vertical)
			this.classPrefix = 'verticaltabs';
		else
			this.classPrefix = 'horizontaltabs';
		this.firePrefix = 'Tabs:';
		this.splitContainer = make_split_pane(this.settings.vertical);
		if (this.settings.vertical) {
			this.tabsPane = this.splitContainer.down('.SplitPane', 1);
			this.panelsPane = this.splitContainer.down('.SplitPane', 0);
		} else {
			this.tabsPane = this.splitContainer.down('.SplitPane', 0);
			this.panelsPane = this.splitContainer.down('.SplitPane', 1);
		}
		this.tabsPane.addClassName(this.classPrefix+'tabspane');
		this.tabsStopper = new Element('br');
		this.tabsPane.insert(this.tabsStopper);
		this.panelsPane.addClassName(this.classPrefix+'panelspane');
		this.container.insert(this.splitContainer);
	},

	add_section: function($super, name, disabled) {
		var section = new AccordionSection(name, disabled, this);
		this.tabsStopper.insert({'before':section.titleDiv});
		this.panelsPane.insert(section.contentDiv);
		this.sections[name] = section;
	}
});

// Inner class used by Accordion and Tabs, not to be used directly by client
var AccordionSection = Class.create({
	initialize: function(name, disabled, widget) {
		this.widget = widget;
		this.name = name;

		// New DOM elements.
		this.titleDiv = new Element('div', {'class':this.widget.classPrefix+'titlediv'});
		this.titleIcon = new Element('img', {'class':this.widget.classPrefix+'titleicon'});
		this.titleSpan = new Element('span', {'class':this.widget.classPrefix+'titlespan'});
		this.titleDiv.insert(
			new Element('table', {'style':'margin:0;border:0;border-collapse:collapse; border-spacing:0'}).insert(
				new Element('tbody').insert(
					new Element('tr').insert(
						new Element('td', {'class':'left'})
					).insert(
						new Element('td', {'class':'middle'}).insert(this.titleIcon).insert(this.titleSpan)
					).insert(
						new Element('td', {'class':'right'})
					)
				)
			)
		);
		this.contentDiv = new Element('div', {'class':this.widget.classPrefix+'contentdiv'});

		// Register DOM events.
		this.titleDiv.observe('click', this.on_click_title.bindAsEventListener(this));

		// Finalize initial look and feel.
		if (disabled)
			this.disable();
		this.hide_content(true);
	},
	update: function(options) {
		if (options.title) {
			this.titleSpan.update(options.title);
			var title = this.titleSpan.innerHTML.stripTags();
			this.titleSpan.title = title;
		}
		
		if (options.icon) {
			if (options.icon.tagName) {
				this.titleIcon.replace(options.icon);
				this.titleIcon = options.icon;
				this.titleIcon.addClassName(this.widget.classPrefix+'titleicon');
			} else {
				this.titleIcon.src = options.icon;
			}
		}
		if (options.content)
			this.contentDiv.update(options.content);
	},
	enable: function(unlock) {
		this.titleDiv.show();
		if (unlock)
			this.unlock();
	},
	disable: function(lock) {
		this.titleDiv.hide();
		this.contentDiv.hide();
		if (lock)
			this.lock();
	},
	lock: function() {
		this.locked = true;
		this.titleDiv.addClassName(this.widget.classPrefix+'titledivlocked');
	},
	unlock: function() {
		this.locked = false;
		this.titleDiv.removeClassName(this.widget.classPrefix+'titledivlocked');
	},
	hide_content: function(dontAnimate) {
		var hideDuration = this.widget.settings.hideDuration;
		if (!hideDuration)
			dontAnimate = true;

		if (this.contentDiv.visible()) {
			if (dontAnimate)
				this.contentDiv.hide();
			else
				new Effect.BlindUp(this.contentDiv, {queue: {limit: 2, position:'end', scope:this.widget.classPrefix+this.widget.container.identify()}, duration:hideDuration});
		}

		this.titleDiv.removeClassName(this.widget.classPrefix+'titledivexpanded');
		this.titleDiv.removeClassName(this.widget.classPrefix+'titledivcollapsed');
		this.titleDiv.addClassName(this.widget.classPrefix+'titledivcollapsed');
	},
	show_content: function(dontAnimate) {
		var showDuration = this.widget.settings.showDuration;
		if (!showDuration)
			dontAnimate = true;

		if (!this.contentDiv.visible()) {
			if (dontAnimate)
				this.contentDiv.show();
			else
				new Effect.BlindDown(this.contentDiv, {queue: {limit: 2, position:'end', scope:this.widget.classPrefix+this.widget.container.identify()}, duration:showDuration});
		}

		this.titleDiv.removeClassName(this.widget.classPrefix+'titledivexpanded');
		this.titleDiv.removeClassName(this.widget.classPrefix+'titledivcollapsed');
		this.titleDiv.addClassName(this.widget.classPrefix+'titledivexpanded');
	},
	on_click_title: function(event) {
		if (this.locked)
			return;

		if (this.widget.fire_event('ClickTitle', {section:this.name, action: (this.contentDiv.visible() && this.widget.settings.collapseCurrentSection) ? 'collapse' : 'expand'}).stopped)
			return;

		if (this.widget.currentSection != this.name)
			this.widget.show_section(this.name);
		else
			this.widget.collapse_all();
	}
});
