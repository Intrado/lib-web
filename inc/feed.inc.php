<?


function feed($buttons,$filters) {
?>
<div class="feed_btn_wrap cf">
<?
	foreach($buttons as $button) {
		echo $button;
	}
?>
</div>


<div class="csec window_aside">
		<h3 id="filterby">Sort By:</h3>
		<ul id="allfilters" class="feedfilter">
		
		<?
			foreach($filters as $key => $filter) {
				echo "<li><a id=\"{$key}filter\" href=\"#\" onclick=\"feed_applyfilter('{$_SERVER["REQUEST_URI"]}','$key'); return false;\"><img src=\"{$filter["icon"]}\" />{$filter["name"]}</a></li>";
			}
		?>
		</ul>
</div><!-- .cesc .window_aside -->
	

<div class="csec window_main">
	
	<div id="pagewrappertop" class="content_recordcount_top"></div>

	<div id="feeditems" class="content_feed">
			<table><tbody>
				<tr>
					<td class=""><img src='img/ajax-loader.gif' /></td>
					<td>
						<div class='feedtitle'>
							<a href=''><?//= _L("Loading Lists") ?></a>
						</div>
					</td>
				</tr>
			</tbody></table>
		</div>
	<div id="pagewrapperbottom" class="content_recordcount_btm"></div>
	
</div><!-- .cesc .window_main -->

<?
}