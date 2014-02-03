<?
class ValCmaAppId extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $SETTINGS;

		$cmaApi = new CmaApiClient(new ApiClient($SETTINGS['cmaserver']['apiurl']), $value);

		return($cmaApi->isValidAppId());
	}
}
?>
