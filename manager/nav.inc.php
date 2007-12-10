<html>
<body>
<image src="manager.png">
<script>
	function getObj(name)
	{
	  if (document.getElementById)
	  {
	  	this.obj = document.getElementById(name);
	  }
	  else if (document.all)
	  {
		this.obj = document.all[name];
	  }
	  else if (document.layers)
	  {
	   	this.obj = document.layers[name];
	  }
	  if(this.obj)
		this.style = this.obj.style;
	}
</script>
<table border = 1>
	<tr>
		<td><a href="customers.php">Customer&nbsp;List</a></td>
		<td><a href="customerimports.php">Customer&nbsp;Imports</a></td>
		<td><a href="customeractivejobs.php">Active&nbsp;Jobs</a></td>
		<td><a href="newcustomer.php">New&nbsp;Customer</a></td>		
		<td><a href="./?logout=1">Log&nbsp;Out</a></td>
	</tr>
</table>
