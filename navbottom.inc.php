						</td>
						<td id='shadowright' valign="top" align="left"><img class="noprint" src="img/shadow_top_right.gif"></td>
					</tr>
					<tr>
						<td id='shadowbottom' valign="top" align="left"><img class="noprint" src="img/shadow_bottom_left.gif"></td>
						<td valign="top" align="left"><img class="noprint" src="img/shadow_bottom_right.gif"></td>
					</tr>
			</table></div>
		</div>
	</div>
</div>
<div id="termsinfo">Use of this system is subject to the <a href="privacy.html" target="_blank">Privacy Policy</a> and <a href="terms.html" target="_blank">Terms of Service</a>
<br>
&copy; 2006 Reliance Communications, Inc.

</div>
<?
print "<div id='logininfo' class='noprint' >Logged in as $USER->firstname $USER->lastname ($USER->login) <br> Current system time is " . date("F jS, Y h:i a (e)") . "</div>";

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
<img id="state" src="img/spacer.gif" width="1" height="1">
<? if ($_GET['debug']) printf("<!-- %0.2f -->", microtime(true) - $PAGETIME) ?>
</body>
</html>
