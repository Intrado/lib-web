/**
 * @author Kee-Yip Chan
 */
// Custom Events:
// container.fire("Accordion:ClickTitle", {section}), if the event is not stopped then show the chosen section.
var Accordion = Class.create({
	// @param settings {hideDuration:0.3, showDuration:0.3}
	initialize: function(container, settings) {
		this.container = $(container);
		this.sections = {};

		this.settings = settings ? settings : {};
		if (!this.settings.hideDuration)
			this.settings.hideDuration = 0.3;
		if (!this.settings.showDuration)
			this.settings.showDuration = 0.3;

		this.currentSection = null;
	},
	add_section: function(name, disabled) {
		this.sections[name] = new AccordionSection(name, disabled, this);
	},
	enable_section: function(name) {
		this.sections[name].sectionDiv.show();
	},
	disable_section: function(name) {
		this.sections[name].sectionDiv.hide();
	},
	// @param options = {title:"", icon:"http://", content:Element or plain html}, not necessary to specify all options
	update_section: function(name, options) {
		this.sections[name].update(options);
	},
	collapse_all: function(ignoredSection, dontAnimate) {
		for (var name in this.sections) {
			if (name != ignoredSection)
				this.sections[name].hide(dontAnimate);
		}
		this.currentSection = null;
	},
	show_section: function(name, dontAnimate) {
		this.collapse_all(name);
		this.sections[name].show(dontAnimate);
		this.currentSection = name;
	}
});

// Inner class used by Accordion, not to be used directly by client
var AccordionSection = Class.create({
	initialize: function(name, disabled, accordion) {
		this.accordion = accordion;
		this.name = name;

		this.sectionDiv = new Element('div', {'class':'accordionsectiondiv'});
		this.titleDiv = new Element('div', {'class':'accordiontitlediv'});
		this.titleIcon = new Element('img', {'class':'accordiontitleicon'});
		this.titleSpan = new Element('span', {'class':'accordiontitlespan'});
		this.titleDiv.insert(this.titleIndicator).insert(this.titleIcon).insert(this.titleSpan);
		this.contentDiv = new Element('div', {'class':'accordioncontentdiv'});

		this.sectionDiv.insert(this.titleDiv).insert(this.contentDiv);
		if (disabled)
			this.sectionDiv.hide();
		this.accordion.container.insert(this.sectionDiv);

		this.titleDiv.observe('click', function(event) {
			if (!this.accordion.container.fire("Accordion:ClickTitle", {section:this.name}).stopped) {
				if (this.accordion.currentSection != this.name)
					this.accordion.show_section(this.name);
				else
					this.accordion.collapse_all();
			}
		}.bindAsEventListener(this));

		this.hide(true);
	},
	hide: function(dontAnimate) {
		if (this.contentDiv.visible()) {
			if (dontAnimate)
				this.contentDiv.hide();
			else
				new Effect.BlindUp(this.contentDiv, {queue: {limit: 2, position:'end', scope:'accordion'+this.accordion.container.identify()}, duration:this.accordion.settings.hideDuration});
		}

		this.sectionDiv.removeClassName('accordionsectiondivexpanded');
		this.sectionDiv.removeClassName('accordionsectiondivcollapsed');
		this.sectionDiv.addClassName('accordionsectiondivcollapsed');
	},
	show: function(dontAnimate) {
		if (!this.contentDiv.visible()) {
			if (dontAnimate)
				this.contentDiv.show();
			else
				new Effect.BlindDown(this.contentDiv, {queue: {limit: 2, position:'end', scope:'accordion'+this.accordion.container.identify()}, duration:accordion.settings.showDuration});
		}

		this.sectionDiv.removeClassName('accordionsectiondivexpanded');
		this.sectionDiv.removeClassName('accordionsectiondivcollapsed');
		this.sectionDiv.addClassName('accordionsectiondivexpanded');
	},
	update: function(options) {
		if (options.title)
			this.titleSpan.update(options.title);
		if (options.icon)
			this.titleIcon.src = options.icon;
		if (options.content)
			this.contentDiv.update(options.content);
	}
});