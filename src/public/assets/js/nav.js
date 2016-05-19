var nav_selection;

function NavToggleOpen (name,child) {
	var listitem = new getObj(name);
	
	if (listitem.obj.open) {
		listitem.obj.open = false;
		hide(child);
		listitem.style.listStyle="disc";
	} else {
		listitem.obj.open = true;
		show(child);
		listitem.style.listStyle="circle";
	}
}

function NavSelect (name) {
	var listitem = new getObj(name);
	var selected = false;
	
	if (!nav_selection ) {
		selected = true;
	}
	
	if ( nav_selection && (nav_selection.obj != listitem.obj )) {
		selected = true;
	}
	
	if (selected) {
		if (nav_selection)
			nav_selection.style.background="";
		nav_selection = listitem;
		nav_selection.style.background="#cccccc";
		return true;
	}
	return false;
}

