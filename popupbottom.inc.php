						</td>
						<td id='shadowright' valign="top" align="left"><img src="img/shadow_top_right.gif"></td>
					</tr>
					<tr>
						<td id='shadowbottom' valign="top" align="left"><img src="img/shadow_bottom_left.gif"></td>
						<td valign="top" align="left"><img src="img/shadow_bottom_right.gif"></td>
					</tr>
			</table></div>
		</div>
	</div>
</div>
<?
if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
if(isset($STATUS) && is_array($STATUS)) {
	foreach($STATUS as $key => $value) {
		$STATUS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.status = \'' . implode('. ', $STATUS) . '.\';</script>';
}
?>
</body>
</html>