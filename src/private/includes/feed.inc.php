<?


function feed($buttons,$sortoptions,$viewoptions = null) {
?>
<div class="feed_btn_wrap cf">
<?
	foreach($buttons as $button) {
		echo $button;
	}
?>
</div>

<div class="csec window_aside">
		<? 
		if ($viewoptions != null) {
			?>
			<h3><?= _L('Views:') ?></h3>
			<ul class="feedfilter feedviewoptions">
			<?
			foreach($viewoptions as $key => $item) {
				echo "<li id=\"feed_view_{$key}\"><a href=\"#\" onclick=\"feed_applyview('{$_SERVER["REQUEST_URI"]}','$key'); return false;\"><img src=\"{$item["icon"]}\" />{$item["name"]}</a></li>";
			}
			echo '</ul>';
		}
		if ($sortoptions != null) {
			?>
			<h3><?= _L('Sort By:') ?></h3>
			<ul id="allfilters" class="feedfilter feedsortbyoptions">
			<?
			foreach($sortoptions as $key => $item) {				
				echo "<li id=\"feed_sortby_{$key}\"><a href=\"#\" onclick=\"feed_applysortby('{$_SERVER["REQUEST_URI"]}','$key'); return false;\"><img src=\"{$item["icon"]}\" />{$item["name"]}</a></li>";
			}
			?>
			</ul>
			<?
		}
		?>
</div><!-- .cesc .window_aside -->

<div class="csec window_main">
	
	<div id="pagewrappertop" class="content_recordcount_top"></div>

	<div id="feeditems" class="content_feed">
			<table><tbody>
				<tr>
					<td class=""><img src='assets/img/ajax-loader.gif' /></td>
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