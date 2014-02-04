<?
class ValCmaAppId extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $SETTINGS;

		$cmaApi = new CmaApiClient(new ApiClient($SETTINGS['cmaserver']['apiurl']), $value);

		if (false === $cmaApi->isValidAppId()) {
			return("{$this->label} must have a valid CMA App. ID");
		}

		return true;
	}
}
?>
