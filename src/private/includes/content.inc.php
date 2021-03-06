<?

function connectToContentServer($type) {
	global $SETTINGS;

	$serversstr = explode(";",$SETTINGS['content'][$type]);

	foreach($serversstr as $str) {
		list($ip,$port,$path) = $server = explode(",",$str);
		if ($fp = fsockopen($ip,$port,$errno,$errstr,0.5))
			return array($fp,$server);
	}
}

function getHttpResponseContents ($fp) {
	$data = fscanf($fp,"HTTP/%f %s %s");
	if ($data[1] != "200")
		return false;

	while (!feof($fp)) {
		$data = stream_get_line($fp,8192,"\r\n");
		if ($data == "")
			break;

		$header = explode(":",$data);
		if (stripos($header[0],"Content-Type") !== false)
			$contenttype = trim($header[1]);
	}
	if ($data = stream_get_contents($fp))
		return array((isset($contenttype) ? $contenttype : NULL),$data);
	else
		return false;
}

function contentGet ($cmid, $base64 = false) {
	$filedata = commsuite_contentGet($cmid);

	if (isset($_REQUEST["api"])) {
		if (!$filedata) {
			return false;
		}
	}

	if (! is_object($filedata)) {
		error_log_helper('content.inc.php::contentGet() - Error retrieving content, possible data integrity issue for content ID=[' . $cmid . ']');
		return null;
	}
	if ($base64)
		return array($filedata->contenttype, base64_encode($filedata->data));
	else
		return array($filedata->contenttype, $filedata->data);
}

function contentGetForCustomerId ($customerid, $contentid) {
	$filedata = commsuite_contentGetForCustomerId($customerid, $contentid);
	// FIXME : SMK notes 2013-05-31 that ASPManager customer edit results in a non-object for the above call
	return(is_object($filedata) ? array($filedata->contenttype, $filedata->data) : array(null, null));
}

// TODO: refactor to use appserver API at some point
function contentPut ($filename,$contenttype, $base64 = false) {
	global $SETTINGS;
	$result = false;

	$content = new Content();

	if (!$base64)
		$content->data = base64_encode(file_get_contents($filename));
	else
		$content->data = file_get_contents($filename);

	$content->contenttype = $contenttype;
	$content->originalcontentid = null;

	// Capture this original content upload's height/width properties (if possible)
	$content->width = $content->height = null;
	$res = getimagesize($filename);
	if (false !== $res) {
		// Make sure this file's mimetype is one that we explicitly support for height/width...
		if (in_array($res[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_JPEG2000, IMAGETYPE_GIF))) {
			$content->width = intval($res[0]);
			$content->height = intval($res[1]);
		}
	}
	if ($content->update()) {
		$result = $content->id;
	}
	
	return $result;
}

function contentDelete($contentid) {
	commsuite_contentDelete($contentid);
}

/**
 * DEPRECATED only here for manager/preview.wav.php
 * @param unknown $text
 * @param unknown $language
 * @param unknown $gender
 * @return Ambigous <boolean, multitype:unknown Ambigous <NULL, unknown> >|boolean
 */
function renderTts ($text,$language,$gender) {
	list($fp,$server) = connectToContentServer("tts"); // FIXME voice name
	list($host,$port,$path) = $server;
	if ($fp) {
		// tts priority 0=normal, 1=high
		$req = "POST " . $path. "?language=" . urlencode($language)
				. "&gender=" . urlencode($gender) . "&priority=1"
				. " HTTP/1.0\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Length: " . strlen($text) . "\r\nConnection: close\r\n\r\n" . $text;
		if (fwrite($fp,$req)) {
			$data = getHttpResponseContents($fp);
			fclose($fp);
			return $data;
		}
	}
	return false;
}

// This function processes $_FILES[$formitemname] and puts a successful file upload into the `content` table, returning an associative array of information upon success. An error message (string) is returned upon failure.
// TODO: make alternative error messages for $foremailattachment.
// TODO: change unsafeext warning message to dump the array of unsafe extensions where the error was caught instead of a hard-coded, broad list is extensions
// TODO: eliminate the maxfilesize argument from the function call since it is worthless - just retrieve with ini_get('upload_max_filesize')
// Returns array('contentid' => $contentid, 'filename' => $filename, 'sizebytes' => $sizebytes) if successful, otherwise returns $errormessage; the client should check for is_string() and is_array() on the return value.
// Either $unsafeext == null or $allowedext == null; both are arrays of file extensions. If $allowedext is set, only those file extensions are accepted.
// Recommended $maxfilesizebytes is 2048K = 2 * 1024 * 1024 [[SMK NOTE - this is only for the error message; the actual setting is a global PHP INI value.]].
// $formitemname is also used for secure_tmpname($formitemname, 'dat').
// $foremailattachment is boolean; if false, error messages are more generic. Otherwise the error messages specifically mention "email attachment."
// $max_dim is null if unused, otherwise a numeric value of the maximum pixel dimension height or width used to reduction scale the uploaded image
// $scaleabove set > 0 to enable reduction scaling for uploaded images to this maximum dimension; SMK added 2012-12-07
function handleFileUpload($formitemname, $maxfilesizebytes, $unsafeext = null, $allowedext = null, $foremailattachment = false, $max_dim = null, $scaleabove = 0) {
	$errormessage = '';
	$uploaderror = true;
	
	if (isset($_FILES[$formitemname]['error']) && $_FILES[$formitemname]['error'] != UPLOAD_ERR_OK) {
		switch($_FILES[$formitemname]['error']) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$errormessage .= $foremailattachment ? _L('The file you uploaded exceeds the maximum attachment limit of %s.', ($maxfilesizebytes / 1024) . 'K') : _L('The file you uploaded exceeds the maximum size limit');
				$uploaderror = true;
				break;

			case UPLOAD_ERR_PARTIAL:
				$errormessage .= _L('The file upload did not complete').' '._L('Please try again').' '._L('If the problem persists').' '._L('please check your network settings');
				$uploaderror = true;
				break;

			case UPLOAD_ERR_NO_FILE:
				$errormessage .= "Please select a file to upload";
				$uploaderror = true;
				break;

			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				$errormessage .= _L('Unable to complete file upload. Please try again');
				$uploaderror = true;
				break;
		}
	} else if(isset($_FILES[$formitemname]) && $_FILES[$formitemname]['tmp_name']) {
		$newname = secure_tmpname($formitemname,".dat");

		$filename = $_FILES[$formitemname]['name'];
		$sizebytes = $_FILES[$formitemname]['size'];
		
		$extdotpos = strrpos($filename,".");
		if ($extdotpos !== false)
			$ext = substr($filename,$extdotpos);

		$mimetype = $_FILES[$formitemname]['type'];
		$uploaderror = true;
		if(!move_uploaded_file($_FILES[$formitemname]['tmp_name'],$newname)) {
			$errormessage .= _L('Unable to complete file upload. Please try again');
		} else if (!is_file($newname) || !is_readable($newname)) {
			$errormessage .= _L('Unable to complete file upload. Please try again');
		} else if ($extdotpos === false) {
			$errormessage .= _L('The file you uploaded does not have a file extension. Please make sure the file has the correct extension and try again');
		} else if ((is_array($allowedext) && !in_array(strtolower($ext), $allowedext)) || (is_array($unsafeext) && array_search(strtolower($ext),$unsafeext) !== false)) {
			$errormessage .= _L('The file you uploaded may pose a security risk and is not allowed. Unsafe file types include: .ade, .adp, .app, .asx, .bas, .bat, .chm, .cmd, .com, .cpl, .crt, .dbx, .dmg, .exe, .hlp,  .hta, .inf, .ins, .isp, .js, .jse, .lnk, .mda, .mdb, .mde, .mdt, .mdw, .mdz, .mht, .msc, .msi, .msp, .mst, .nch, .ops, .pcd, .pif, .prf, .reg, .scf, .scr, .sct, .shb, .shs, .url, .vb, .vbe, .vbs, .wms, .wsc, .wsf, .wsh, and .zip.');
		} else if ($_FILES[$formitemname]['size'] >= $maxfilesizebytes) {
			$errormessage .= _L('The file you uploaded exceeds the maximum limit of %s.', ($maxfilesizebytes / 1024) . 'K');
		} else if ($_FILES[$formitemname]['size'] <= 0) {
			$errormessage .= _L('The file you uploaded appears to be empty. Please check the file and try again');
		} else if (($scaleabove > 0) && (! resizeImage($newname, $filename, $scaleabove))) {
			// resizeImage passes through gracefully if the file does not have an image file extension
			$basename = basename($filename);
			$errormessage .= _L('The image resize failed for file [%s]', $basename);
		} else {
			$contentid = contentPut($newname,$mimetype);
			if ($contentid) {
				$uploaderror = false;
			} else {
				$errormessage .= _L('Unable to upload email attachment data');
				$errormessage .= _L('Unable to complete file upload. Please try again');
			}
		}
		unlink($newname);
	}
	
	if (!$uploaderror) {
		return array('contentid' => $contentid, 'filename' => $filename, 'sizebytes' => $sizebytes);
	} else {
		return $errormessage;
	}
}

// This function is provided so that we can read an image stream from the customerdb.content
// table, scale it, and then write it back to the same or a different content record.
function resizeImageStream($imageStream, $width, $height, $type) {
	// (0) Make sure we have good environment to work with
	if (! function_exists('gd_info')) {
		error_log_helper('No GD library');
		return null;
	}
	if ($type == 'image/gif') {
		// Only necessary for handling GIF images
		//if (PHP_VERSION_ID < 505) {
		if (version_compare(phpversion(), '5.5.0', '<')) {
			error_log_helper('PHP Version >= 5.5.0; needed for imagepalettetotruecolor()');
			return null;
		}
	}

	// (1) width/height <= 0 means no scaling
	if (($width <= 0) || ($height <= 0)) return null;

	// (2) Get an image from the stream
	$r_img = imagecreatefromstring($imageStream);
	if (! is_resource($r_img)) {
		error_log_helper('Failed to create image resource from source stream');
		return null;
	}

	// (3) Convert a palettized (GIF) image to true color (NOOP if already true color)
	if ($type == 'image/gif') {
		if (! imagepalettetotruecolor($r_img)) { // PHP 5.5.0+ required!
			error_log_helper('Failed to convert source stream image resource to a true color palette');
			return null;
		}
	}

	// (4) Make the resized image
	$r_img_scaled = imagecreatetruecolor($width, $height);
	if (! is_resource($r_img_scaled)) {
		error_log_helper('Failed to create a new image resource to use for resizing');
		return null;
	}
	$res = imagecopyresampled($r_img_scaled, $r_img, 0, 0, 0, 0, $width, $height, imagesx($r_img), imagesy($r_img));
	imagedestroy($r_img);
	if (! $res) {
		error_log_helper('Failed to resample/resize the image');
		return null;
	}

	// (5) Capture the retulting compressed image output stream
	ob_start();
	switch ($type) {
		case 'image/jpg':
		case 'image/jpeg':
			$res = imagejpeg($r_img_scaled, NULL, 90);
			break;

		case 'image/png':
			$res = imagepng($r_img_scaled, NULL, 9);
			break;

		case 'image/gif':
			$res = imagegif($r_img_scaled, NULL);
			break;

		case 'image/bmp':
		case 'image/bitmap':
		case 'image/x-portable-bitmap':
			$res = image2wbmp($r_img_scaled, NULL);
			break;
		default:
			return null;
	}
	imagedestroy($r_img_scaled);
	$resizedStream = ob_get_contents();
	ob_end_clean();
	if (! $res) {
		error_log_helper('Failed to convert the resized image to a JPEG stream (??)');
		return null;
	}

	return $resizedStream;
}

// Filepath is the location of the raw POST data that PHP captured from the uploaded file
// Filename is the name of the file that the user uploaded
// MAX_DIM is the maximum dimension in pixels for either height/width before scaling kicks in
// Returns true if the file was scaled down, otherwise false if no change was made
function resizeImage($filepath, $filename, $MAX_DIM) {

	// (0) A MAX_DIM <= 0 means no scaling
	if ($MAX_DIM <= 0) return false;

	// (1) Make sure we have good GD library to work with
	if (! function_exists('gd_info')) return false;

	// (2) Load the image from disk; method is based on extension of filename
	$ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
	switch ($ext) {

		// Only these file extensions are supported
		case 'gif':
			$r_img = imagecreatefromgif($filepath);
			break;

		case 'jpg':
		case 'jpeg':
			$r_img = imagecreatefromjpeg($filepath);
			break;

		case 'png':
			$r_img = imagecreatefrompng($filepath);
			break;

		case 'bmp':
			$r_img = imagecreatefromwbmp($filepath);
			break;

		// Any other (or empty) file extension will not be scaled as an image
		default:
			return(true);
	}
	if (! is_resource($r_img)) return false;
	
	// (3) Get the image dimensions
	$width = imagesx($r_img);
	$height = imagesy($r_img);
	if (($width * $height) == 0) return false;

	// If both dimensions are already under the max then there is nothing to do
	if (($width <= $MAX_DIM) && ($height <= $MAX_DIM)) return(true);

	// (4) Figure out the scaled dimensions
	$width_factor = $width / $MAX_DIM;
	$height_factor = $height / $MAX_DIM;
	$factor = max($width_factor, $height_factor);
	$width_new = $width / $factor;
	$height_new = $height / $factor;

	// (5) Make the resized image
	$r_img_scaled = imagecreatetruecolor($width_new, $height_new);
	if (! is_resource($r_img_scaled)) return false;
	$res = imagecopyresized($r_img_scaled, $r_img, 0, 0, 0, 0, $width_new, $height_new, $width, $height);
	if (! $res) return false;

	// We're done with r_img, so free up that RAM explicitly
	imagedestroy($r_img);

	// (6) Put the scaled image back out to disk
	switch ($ext) {
		case 'gif':
			$res = imagegif($r_img_scaled, $filepath);
			break;

		case 'png':
			$res = imagepng($r_img_scaled, $filepath, 2);
			break;

		case 'jpg':
		case 'jpeg':
			$res = imagejpeg($r_img_scaled, $filepath);
			break;

		case 'bmp':
			$res = image2wbmp($r_img_scaled, $filepath);
			break;

		default:
			// Should be impossible to get here unless
			// somebody tampers with the value of $ext
			$res = false;
			break;
	}
	if (! $res) return false;

	// (7) And finally release the RAM from our scaled image as well
	imagedestroy($r_img_scaled);

	return(true);
}

?>
