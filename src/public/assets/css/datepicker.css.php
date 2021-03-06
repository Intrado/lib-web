<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

?>
/**
 * datePicker widget using Prototype and Scriptaculous.
 * (c) 2007-2008 Mathieu Jondet <mathieu@eulerian.com>
 * Eulerian Technologies
 * (c) 2009 Titi Ala'ilima <tigre@pobox.com>
 *
 * DatePicker is freely distributable under the same terms as Prototype.
 *
 * v1.0.0
 */

div.datepicker {
 position: absolute;
 text-align: center;
 border: 1px #C4D5E3 solid;
 font-family: arial;
 background: #FFFFFF;
 font-size: 10px;
 padding: 0px;
}
div.datepicker table {
 font-size: 10px;
 margin: 0px;
 padding: 0px;
 text-align: center;
 width: 180px;
}
div.datepicker table thead tr th {
 font-size: 12px;
 font-weight: bold;
 background: #e9eff4;
 border-bottom:1px solid #c4d5e3;
 padding: 0px;
 margin: 0px;
}
div.datepicker table tbody tr {
 border: 1px white solid;
 margin: 0px;
 padding: 0px;
}
div.datepicker table tbody tr td {
 border: 1px #eaeaea solid;
 margin: 0px;
 padding: 0px;
 text-align: center;
}
div.datepicker table tbody tr td:hover,
div.datepicker table tbody tr td.outbound:hover,
div.datepicker table tbody tr td.today:hover {
 border: 1px #c4d5e3 solid;
 background: #e9eff4;
 cursor: pointer;
}
div.datepicker table tbody tr td.wday {
 border: 1px #ffffff solid;
 background: #ffffff;
 cursor: text;
}
div.datepicker table tbody tr td.outbound {
 background: #e8e4e4;
}
div.datepicker table tbody tr td.today {
 border: 1px #16518e solid;
 background: #c4d5e3;
}
div.datepicker table tbody tr td.nclick,
div.datepicker table tbody tr td.nclick_outbound,
div.datepicker table tbody tr td.nclick_today {
 cursor:default; color:#aaa;
}
div.datepicker table tbody tr td.nclick_outbound {
 background:#E8E4E4;
}
div.datepicker table tbody tr td.nclick_today {
 background:#c4d5e3;
}
div.datepicker table tbody tr td.nclick:hover,
div.datepicker table tbody tr td.nclick_outbound:hover,
div.datepicker table tbody tr td.nclick_today:hover {
 border: 1px #eaeaea solid;
 background: #FFF;
}
div.datepicker table tbody tr td.nclick_outbound:hover {
 background:#E8E4E4;
}
div.datepicker table tbody tr td.nclick_today:hover {
 background:#c4d5e3;
}
div.datepicker table tfoot {
 font-size: 10px;
 background: #e9eff4;
 border-top:1px solid #c4d5e3;
 cursor: pointer;
 text-align: center;
 padding: 0px;
}

