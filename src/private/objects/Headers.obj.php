<?php

class Headers {
	public function send_csv_headers($filename) {
		// set header
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename={$filename}");
		header("Content-type: application/vnd.ms-excel");
	}
}

