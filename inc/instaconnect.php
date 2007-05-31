<?php

	class instaconnect
	{
		var $instaUSER = ""; /* Your InstaConnect Username */
		var $instaPWD  = ""; /* Your InstaConnect Password */

		var $wsdlGWSERVER = "http://instaconnect.golivemobile.com/gatewayws.php?wsdl";
		var $wsdlMMSERVER = "http://instaconnect.golivemobile.com/multimediaws.php?wsdl";
		var $wsdlADMServer = "http://instaconnect.golivemobile.com/administrativews.php?wsdl";

		var $client = null;

		/**
		 * send a SMS message to a single phone number.
		 *
		 * @param String $msisdn
		 * @param String $msg
		 * @return Array
		 */
		function Send_SMS($msisdn,$msg) {
			$this->client = new SoapClient($this->wsdlGWSERVER,true,'','','','');
			$param = '<user xsi:type="xsd:string">' . $this->instaUSER . '</user><password xsi:type="xsd:string">' . $this->instaPWD . '</password><msisdn xsi:type="xsd:string">' . $msisdn . '</msisdn><body xsi:type="xsd:string">' . $msg . '</body>';
			$result = $this->client->call('ws_sendsms',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}


		/**
		 * send a SMS message to a group of phone numbers.
		 *
		 * @param Array $msisdn
		 * @param String $msg
		 * @return Array
		 */
		function Send_Bulk_SMS($msisdn,$msg) {
			$this->client = new SoapClient($this->wsdlGWSERVER,true,'','','','');
			if(!is_array($msisdn))
				die("First argument should be an array");
			$param = '<user xsi:type="xsd:string">' . $this->instaUSER . '</user><password xsi:type="xsd:string">' . $this->instaPWD . '</password>';
			$param = $param . '<msisdn xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType="xsd:string[' . count($msisdn) .']">';
			for($i=0;$i < count($msisdn); $i++)
				$param = $param . '<item xsi:type="xsd:string">'.$msisdn[$i].'</item>';
			$param = $param . '</msisdn>';
			$param =  $param . '<body xsi:type="xsd:string">' . $msg . '</body>';

			$result = $this->client->call('ws_sendbulksms',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}


		/**
		 * Trigger a Premium SMS charge to a mobile phone number.
		 *
		 * @param String $msisdn
		 * @param String $msg
		 * @param String $carrier
		 * @param String $rate
		 * @return Array
		 */
		function Send_Charge($msisdn,$msg,$carrier,$rate) {
			$this->client = new SoapClient($this->wsdlGWSERVER,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><msisdn xsi:type="xsd:string">'. $msisdn  .'</msisdn><body xsi:type="xsd:string">'.$msg.'</body><carrier xsi:type="xsd:string">'.$carrier.'</carrier><rate xsi:type="xsd:string">'. $rate.'</rate>';
			$result = $this->client->call('ws_sendcharge',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Send a WAP Push ("Service Indication") to a mobile phone without intervention by the Orbit Multimedia device transcoding engine.
		 *
		 * @param String $msisdn
		 * @param String $carrier
		 * @param String $msgURL
		 * @return Array
		 */
		function Send_WAP($msisdn,$carrier,$msgURL) {
			$this->client = new SoapClient($this->wsdlGWSERVER,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><msisdn xsi:type="xsd:string">'. $msisdn  .'</msisdn><carrier xsi:type="xsd:string">'.$carrier.'</carrier><mediaurl xsi:type="xsd:string">'. $msgURL.'</mediaurl>';
			$result = $this->client->call('ws_sendwap',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * initiate a web-based optin.
		 *
		 * @param String $msisdn
		 * @param String $keyword
		 * @return Array
		 */
		function Web_Optin($host,$keyword,$carrier,$msisdn) {
			$this->client = new SoapClient($this->wsdlGWSERVER,true,'','','','');
			$param = '<host xsi:type="xsd:string">'. $host .'</host><keyword xsi:type="xsd:string">'. $keyword .'</keyword><carrier xsi:type="xsd:string">'. $carrier.'</carrier><msisdn xsi:type="xsd:string">'. $msisdn  .'</msisdn>';
			$result = $this->client->call('ws_weboptin',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Push content to mobile
		 *
		 * @param String $msisdn
		 * @param String $carrier
		 * @param String $orbitid
		 * @return Array
		 */
		function Orbit_ContentPush($msisdn,$carrier,$orbitid) {
			$this->client = new SoapClient($this->wsdlMMSERVER,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><msisdn xsi:type="xsd:string">'. $msisdn  .'</msisdn><carrier xsi:type="xsd:string">'. $carrier.'</carrier><orbitid xsi:type="xsd:string">'. $orbitid.'</orbitid>';
			$result = $this->client->call('ws_orbitpush',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Upload a media file to Orbit and receive a unique Orbit File ID
		 *
		 * @param String $mediaURL
		 * @return Array
		 */
		function Orbit_Upload($mediaURL) {
			$this->client = new SoapClient($this->wsdlMMSERVER,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><mediaurl xsi:type="xsd:string">'. mediaURL.'</mediaurl>';
			$result = $this->client->call('ws_orbitupload',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Delete a media file from your content catalogue in Orbit Multimedia.
		 *
		 * @param String $orbitid
		 * @return Array
		 */
		function Orbit_Delete($orbitid) {
			$this->client = new SoapClient($this->wsdlMMSERVER,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><orbitid xsi:type="xsd:string">'. mediaURL.'</orbitid>';
			$result = $this->client->call('ws_orbitdelete',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Check balance for your prepaid number
		 *
		 * @return Array
		 */
		function Balance_Check() {
			$this->client = new SoapClient($this->wsdlADMServer,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password>';
			$result = $this->client->call('ws_balcheck',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Find the Wireless Carrier of given phonenumber
		 *
		 * @param String $phoneNumber
		 * @return Array
		 */
		function LNP_Check($phoneNumber) {
			$this->client = new SoapClient($this->wsdlADMServer,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><phonenumber xsi:type="xsd:string">'. $this->instaPWD .'</phonenumber>';
			$result = $this->client->call('ws_lnpcheck',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Find the city and state of a phone number.
		 *
		 * @param String $phoneNumber
		 * @return Array
		 */
		function Geo_Check($phoneNumber) {
			$this->client = new SoapClient($this->wsdlADMServer,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><phonenumber xsi:type="xsd:string">'. $this->instaPWD .'</phonenumber>';
			$result = $this->client->call('ws_geocheck',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Find approximate address of a phone number, based on local switching exchange data.
		 *
		 * @param String $phoneNumber
		 * @return Array
		 */
		function Address_Check($phoneNumber) {
			$this->client = new SoapClient($this->wsdlADMServer,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><phonenumber xsi:type="xsd:string">'. $this->instaPWD .'</phonenumber>';
			$result = $this->client->call('ws_addresscheck',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}

		/**
		 * Check if given phonenumber is wireless phonenumber
		 *
		 * @param String $phoneNumber
		 * @return Array
		 */
		function Wireless_Check($phoneNumber) {
			$this->client = new SoapClient($this->wsdlADMServer,true,'','','','');
			$param = '<user xsi:type="xsd:string">'. $this->instaUSER .'</user><password xsi:type="xsd:string">'. $this->instaPWD .'</password><phonenumber xsi:type="xsd:string">'. $this->instaPWD .'</phonenumber>';
			$result = $this->client->call('ws_wirelesscheck',$param);
			if ($this->client->fault)
				return $result;
			else {
				$err = $this->client->getError();
				if ($err)
					return  $err;
				else
					return $result;
			}
		}
	}

?>