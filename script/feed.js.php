<?

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");

?>

function feed_page(url,event) {
	activepage = event.element().value;
	feed_applyfilter(url,currentfilter);
}

function feed_applyfilter(url,filter) {
	new Ajax.Request(url, {
		method:'get',
		parameters:{ajax:true,filter:filter,pagestart:activepage},
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				$('feeditems').update(new Element('div', {'class': 'content_feed'}));
				for (var i = 0; i < result.list.length; i++) {
					var item = result.list[i];
					var msg = new Element('div', {'class': 'feed_item cf'});

					// insert icon
					msg.insert(
								new Element('a', {'class': 'msg_icon', 'href': item.defaultlink}).insert(
									new Element('img', {'src': item.icon})
								)
						);
					
					var feedWrap = new Element('div', {'class': 'feed_wrap'});
					
					// insert title and content details
					feedWrap.insert(
						new Element('a', {'class': 'feed_title', 'href': item.defaultlink}).insert(
							item.title
							)
						);
					
					if (item.details) {
						feedWrap.insert(
							new Element('div', {'class': 'feedsubtitle'}).insert(
									new Element('a', {'href': item.defaultlink}).insert(
										item.details
									)
								)
							);
					}
					
					feedWrap.insert(
							new Element('div', {'class': 'feed_detail'}).insert(
								item.content
							)
						);
					
					if (item.publishmessage) {
						feedWrap.insert(
							new Element('a', {'class': 'feed_subtitle', 'href': item.defaultlink}).insert(
										new Element('img', {'src': 'img/icons/diagona/10/031.gif'})
									).insert(
										item.publishmessage
									)
							);
					}
					
					msg.insert(feedWrap);
					
					// insert tools (if there are any)
					if (item.tools) {
						msg.insert(
								item.tools
						);
					}
					
					$('feeditems').down('div').insert(msg);
				}
				
				var pagetop = new Element('div',{'class':'content_recordcount'}).update(result.pageinfo[3]);
				var pagebottom = new Element('div',{'class':'content_recordcount'}).update(result.pageinfo[3]);

				var selecttop = new Element('select', {'id':'selecttop'});
				var selectbottom = new Element('select', {'id':'selectbottom'});
				for (var x = 0; x < result.pageinfo[0]; x++) {
					var offset = x * result.pageinfo[1];
					var selected = (result.pageinfo[2] == x+1);
					selecttop.insert(new Element('option', {'value': offset,selected:selected}).update('Page ' + (x+1)));
					selectbottom.insert(new Element('option', {'value': offset,selected:selected}).update('Page ' + (x+1)));
				}
				pagetop.insert(selecttop);
				pagebottom.insert(selectbottom);
				$('pagewrappertop').update(pagetop);
				$('pagewrapperbottom').update(pagebottom);

				currentfilter = filter
				$('selecttop').observe('change',feed_page.curry(url));
				$('selectbottom').observe('change',feed_page.curry(url));

				var filtercolor = $('filterby').getStyle('color');
				if(!filtercolor)
					filtercolor = '#000';

				size = filtes.length;
				for(i=0;i < size;i++){
					$(filtes[i] + 'filter').setStyle({color: filtercolor, fontWeight: 'normal'});
				}
				$(filter + 'filter').setStyle({
					 color: '#000000',
					 fontWeight: 'bold'
				});
			}
		}
	});
}
