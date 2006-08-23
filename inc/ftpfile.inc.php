<?

//copies the file to the FTP site in the /customerid/uploadpath/ directory
function uploadImportFile ($filename, $customerid, $uploadpath, $destfilename = "data.csv") {
	global $SETTINGS;
	$result = false;

	if ($ftpcon = ftp_connect($SETTINGS['import']['ftphost'],$SETTINGS['import']['ftpport'])) {
			$user = $SETTINGS['import']['ftpuser'];
			$pass = $SETTINGS['import']['ftppass'];
			ftp_login($ftpcon,$user,$pass);

			ftp_pasv($ftpcon, true);

			//TODO always brute force or check for existing?
			@ftp_mkdir($ftpcon,$customerid);
			@ftp_mkdir($ftpcon,$customerid . "/" . $uploadpath);

			if (ftp_chdir($ftpcon,$customerid . "/" . $uploadpath))
				if (ftp_put($ftpcon, $destfilename, $filename, FTP_BINARY))
					$result = true;
				else echo "cant put $filename";
			else echo "can't chdir";

	} else echo "can't connect";
	return $result;
}


//returns the URL for opening the import file via FTP
function getImportFileURL ($customerid, $uploadpath, $destfilename = "data.csv") {
	global $SETTINGS;
	$url = "ftp://";
	$url .= $SETTINGS['import']['ftpuser'] . ":" . $SETTINGS['import']['ftppass'];
	$url .= "@" . $SETTINGS['import']['ftphost'] . ":" . $SETTINGS['import']['ftpport'];
	$url .= "/" . $customerid . "/" . $uploadpath . "/$destfilename";

	return $url;
}


?>