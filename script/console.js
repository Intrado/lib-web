/**
 * For IE, console.log is not defined. Here we define it
 *
 * CHANGE LOG:
   * SMK created 2013-01-08
 */
if (typeof console !== 'object') {
	console = {
		consolewin: 0,

		log: function(msg) {
			if (typeof this.consolewin !== 'object') return;
			var consoleoutput = this.consolewin.document.getElementById('consoleoutput');
			if (! consoleoutput) return;
			consoleoutput.innerHTML = '<span class="msg">' + msg + '</span><br/>' + consoleoutput.innerHTML;
		},

		clear: function() {
			if (typeof this.consolewin !== 'object') return;
			var consoleoutput = this.consolewin.document.getElementById('consoleoutput');
			if (! consoleoutput) return;
			consoleoutput.innerHTML = '';
		},

		init: function() {
			this.consolewin = window.open('', 'consolewin', 'width=600,height=200,location=no,resizable=yes,scrollbars=yes');
			var d = this.consolewin.document;
			d.open();
			d.write('<html>');
			d.writeln('	<head>');
			d.writeln('		<style>');
			d.writeln('			html,body { font-family: Arial; font-size: 10px; margin: 0px; padding: 0px; width: 100%; height: 100%;}');
			d.writeln('			div.controls { position: fixed; top: 0px; left: 0px; width: 100%; display: inline-block; background-color: #666666; color: white; font-weight: bold; padding: 2px; z-index: 1; text-align: right; }');
			d.writeln('			a { text-decoration: none; color: white; }');
			d.writeln('			span.msg { nowrap: nowrap; }');
			d.writeln('			#consoleoutput { padding-left: 10px; position: absolute; top: 16px; z-index: 0;}');
			d.writeln('		</style>');
			d.writeln('	</head>');
			d.writeln('	<body>');
			d.writeln('		<div class="controls"><a href="#" onclick="self.opener.console.clear(); self.focus();">CLEAR</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>');
			d.writeln('		<div id="consoleoutput"></div>');
			d.writeln('	</body>');
			d.writeln('</html>');
			d.close();
			this.log('Console logging initialized!');
		}
	};

	console.init();
}
else {
	console.log('Console logging already available');
}

