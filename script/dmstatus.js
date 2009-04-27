
function nl2br (str, is_xhtml) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Philip Peterson
    // +   improved by: Onno Marsman
    // +   improved by: Atli Þór
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brettz9.blogspot.com)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: nl2br('Kevin\nvan\nZonneveld');
    // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
    // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
    // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
    // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
    // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
 
    var breakTag = '';
 
    breakTag = '<br />';
    if (typeof is_xhtml != 'undefined' && !is_xhtml) {
        breakTag = '<br>';
    }
 
    return "<pre>" + (str + '').replace(/([^>]?)\n/g, '$1'+ breakTag +'\n') + "</pre>";
}

function dateDiff(startdate, enddate) {
// modified from http://snippets.dzone.com/posts/show/623

   var intSpan;
   var intYears;
   var intMonths;
   var intWeeks;
   var intDays;
   var intHours;
   var intMinutes;
   var intSeconds;
   var strHowOld;

      // get time span between two dates in milliseconds
      intSpan = enddate - startdate;

      // get number of weeks
      intWeeks = Math.floor(intSpan / 604800000);

      // subtract weeks from time span
      intSpan = intSpan - (intWeeks * 604800000);
      
      // get number of days
      intDays = Math.floor(intSpan / 86400000);

      // subtract days from time span
      intSpan = intSpan - (intDays * 86400000);

      // get number of hours
      intHours = Math.floor(intSpan / 3600000);
    
      // subtract hours from time span
      intSpan = intSpan - (intHours * 3600000);

      // get number of minutes
      intMinutes = Math.floor(intSpan / 60000);

      // subtract minutes from time span
      intSpan = intSpan - (intMinutes * 60000);

      // get number of seconds
      intSeconds = Math.floor(intSpan / 1000);

      // create output string     
      if ( intYears > 0 )
         if ( intYears > 1 )
            strHowOld = intYears.toString() + ' Years';
         else
            strHowOld = intYears.toString() + ' Year';
      else
         strHowOld = '';

      if ( intMonths > 0 )
         if ( intMonths > 1 )
            strHowOld = strHowOld + ' ' + intMonths.toString() + ' Months';
         else
            strHowOld = strHowOld + ' ' + intMonths.toString() + ' Month';
           
      if ( intWeeks > 0 )
         if ( intWeeks > 1 )
            strHowOld = strHowOld + ' ' + intWeeks.toString() + ' Weeks';
         else
            strHowOld = strHowOld + ' ' + intWeeks.toString() + ' Week';

      if ( intDays > 0 )
         if ( intDays > 1 )
            strHowOld = strHowOld + ' ' + intDays.toString() + ' Days';
         else
            strHowOld = strHowOld + ' ' + intDays.toString() + ' Day';

      if ( intHours > 0 )
         if ( intHours > 1 )
            strHowOld = strHowOld + ' ' + intHours.toString() + ' Hours';
         else
            strHowOld = strHowOld + ' ' + intHours.toString() + ' Hour';
 
      if ( intMinutes > 0 )
         if ( intMinutes > 1 )
            strHowOld = strHowOld + ' ' + intMinutes.toString() + ' Minutes';
         else
            strHowOld = strHowOld + ' ' + intMinutes.toString() + ' Minute';

      if ( intSeconds > 0 )
         if ( intSeconds > 1 )
            strHowOld = strHowOld + ' ' + intSeconds.toString() + ' Seconds';
         else
            strHowOld = strHowOld + ' ' + intSeconds.toString() + ' Second';
      
      return strHowOld;
}

function doajax()
{
	new Ajax.Request('dmstatus.php?ajax', { method:'get',
		onSuccess: function(result) {
			var status = result.responseText.evalJSON();
			var avg;
			var fieldname;
			var dispatchers = new Array();
			var dindex = 0;
			var dname;
			var key;
			
			for (key in status[0]) {
				if (key.indexOf("comerr") >= 0) {
					dname = key.substr(7);
					dispatchers[dindex++] = dname;
				}
			}
			/*
			for (dindex in dispatchers) {
				dname = dispatchers[dindex];
				fieldname = "comerr-" + dname;
				$(fieldname).update(status[0][fieldname]);
				fieldname = "comtimeout-" + dname;
				$(fieldname).update(status[0][fieldname]);
				fieldname = "comreadtimeout-" + dname;
				$(fieldname).update(status[0][fieldname]);
			}
			*/
			
			// TODO set locale
			var starttime = new Date(parseInt(status[0]['startuptime']));
			var curtime = new Date(parseInt(status[0]['currenttime']));
			var mytime = new Date();
			if (Math.abs(mytime - curtime) < (5*60*1000))
				 $('currenttime').update(curtime.toLocaleString());
			else
				$('currenttime').update("<font color=\"red\">" + curtime.toLocaleString() + " (stale data, lost connection)</font>");
			
			// general
			if (status[0]['dmenabled'])
				$('dmenabled').update("Enabled");
			else
				$('dmenabled').update("<font color=\"red\">Disabled</font>");
			
			$('startuptime').update(starttime.toLocaleString());
			
			$('sysrunning').update(dateDiff(starttime, curtime));
			
			$('clockresetcount').update(status[0]['clockresetcount']);
			// resource allocation
			$('resactout').update(status[0]['resactout']);
			$('residleout').update(status[0]['residleout']);
			$('resactin').update(status[0]['resactin']);
			$('residlein').update(status[0]['residlein']);
			$('resthrotsched').update(status[0]['resthrotsched']);
			$('resthroterr').update(status[0]['resthroterr']);
			$('restotal').update(status[0]['restotal']);
			// call results
			$('A').update(status[0]['A']);
			$('M').update(status[0]['M']);
			$('B').update(status[0]['B']);
			$('N').update(status[0]['N']);
			$('X').update(status[0]['X']);
			$('F').update(status[0]['F']);
			$('TB').update(status[0]['TB']);
			$('failures').update(status[0]['failures']);
			var dialtime = parseFloat(status[0]['dialtime']);
			$('dialtime').update(dialtime.toFixed(2));
			var billtime = parseFloat(status[0]['billtime']);
			$('billtime').update(billtime.toFixed(2));
			// cache
			$('cachelocation').update(status[0]['cachelocation']);
			$('cachemax').update(status[0]['cachemax']);
			$('cachesize').update(status[0]['cachesize']);
			$('cachefilecount').update(status[0]['cachefilecount']);
			$('cachedelcount').update(status[0]['cachedelcount']);
			$('cachehit').update(status[0]['cachehit']);
			$('cachemiss').update(status[0]['cachemiss']);
			// download audio content
			$('getcontentapicount').update(status[0]['getcontentapicount']);
			$('getcontentapitime').update(status[0]['getcontentapitime']);
			if (status[0]['getcontentapicount'] == 0)
				$('getcontentapiavg').update("0");
			else {
				avg = status[0]['getcontentapitime'] / status[0]['getcontentapicount'];
				$('getcontentapiavg').update(avg.toFixed(2));
			}
			
			// upload audio content
			$('putcontentapicount').update(status[0]['putcontentapicount']);
			$('putcontentapitime').update(status[0]['putcontentapitime']);
			if (status[0]['putcontentapicount'] == 0)
				$('putcontentapiavg').update("0");
			else {
				avg = status[0]['putcontentapitime'] / status[0]['putcontentapicount'];
				$('putcontentapiavg').update(avg.toFixed(2));
			}
			// text-to-speech content
			$('ttsapicount').update(status[0]['ttsapicount']);
			$('ttsapitime').update(status[0]['ttsapitime']);
			if (status[0]['ttsapicount'] == 0)
				$('ttsapiavg').update("0");
			else {
				avg = status[0]['ttsapitime'] / status[0]['ttsapicount'];
				$('ttsapiavg').update(avg.toFixed(2));
			}
			// continue task request
			$('continueapicount').update(status[0]['continueapicount']);
			$('continueapitime').update(status[0]['continueapitime']);
			if (status[0]['continueapicount'] == 0)
				$('continueapiavg').update("0");
			else {
				avg = status[0]['continueapitime'] / status[0]['continueapicount'];
				$('continueapiavg').update(avg.toFixed(2));
			}

			// system details
			$('uptime').update(nl2br(status[0]['uptime']));
			$('diskspace').update(nl2br(status[0]['diskspace']));
			$('memory').update(nl2br(status[0]['memory']));
						
			var numactive = 0;
			var numcompleted = 0;
			var activeresources = "<table><th>ID</th><th>Start Time</th><th>Status</th><th>Direction</th>";
			var completedresources = "<table><th>ID</th><th>Start Time</th><th>Result</th>";
			var i = 1;
			while (i<status.length) {
				var s = status[i];
				var starttime = new Date(parseInt(s['starttime']));
				if (s['rstatus'] == "RESULT") {
					var result = "";
					if (s['result'] == 'A')
						result = "Answered";
					else if (s['result'] == 'M')
						result = "Machine";
					else if (s['result'] == 'B')
						result = "Busy";
					else if (s['result'] == 'N')
						result = "No Answer";
					else if (s['result'] == 'X')
						result = "Disconnect";
					else if (s['result'] == 'F')
						result = "Unknown";
					else if (s['result'] == 'I')
						result = "INBOUND";
					completedresources += "<tr><td>" + s['name'] + "</td><td>" + starttime.toLocaleTimeString() + "</td><td>" + result + "</td></tr>";
					numcompleted++;
				} else {
					if (s['rtype'] == "INBOUND" && s['rstatus'] == "IDLE") {
						// do not display
					} else {
						activeresources += "<tr><td>" + s['name'] + "</td><td>" + starttime.toLocaleTimeString() + "</td><td>" + s['rstatus'] + "</td><td>" + s['rtype'] + "</td></tr>";
						numactive++;
					}
				}
				i++;
			}
			activeresources += "</table>";
			completedresources += "</table>";

			if (numactive > 0)
				$('activeresources').update(activeresources);
			else
				$('activeresources').update("There are no active resources at this time.  The system is idle.");
				
			if (numcompleted > 0)
				$('completedresources').update(completedresources);
			else
				$('completedresources').update("");
				
			for (dindex in dispatchers) {
				dname = dispatchers[dindex];
				fieldname = "comerr-" + dname;
				$(fieldname).update(status[0][fieldname]);
				fieldname = "comtimeout-" + dname;
				$(fieldname).update(status[0][fieldname]);
				fieldname = "comreadtimeout-" + dname;
				$(fieldname).update(status[0][fieldname]);
			}

		}
	});
}

// timeout after 30 minutes idle
setTimeout('window.location="index.php"', 30*60*1000);

// draw initial values
doajax();

// refresh every 10 seconds (poststatus from dm comes every 15 seconds)
new PeriodicalExecuter(doajax, 10);


