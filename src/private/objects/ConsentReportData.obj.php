<?php

$thisDir = dirname(__FILE__);
$baseDir = dirname(dirname($thisDir));
$includesDir = "{$baseDir}/includes";
require_once("{$includesDir}/common.inc.php");

class ConsentReportData {

	// queries the customer db for all persons and their associated phone number.
	// - accepts a job ID to only return people contacted for that specific job
	// - this function should take paging into consideration for HTML results
	// - it should also be able to return ALL results for CSV download
	//
	// @params $customerId::String [$jobId::String, $pagingLowerBoundary::Int, $pagingUpperBoundary::Int]
	// @returns $contacts::Array (array of objects containg the person data and thei)
	public function generateGetContactsQuery ( $phoneNumber = null, $jobId = null ) {

		// find all the persons for this customer
		$query = "SELECT pkey, 
						 f01 AS first_name, 
						 f02 AS last_name, 
						 phone
				  FROM person
				  INNER JOIN phone 
				  ON phone.personid = person.id 
				  WHERE person.deleted = 0 ";

		if( $phoneNumber ) {
			$query .= "AND phone.phone = '" . $phoneNumber . "' ";
		}

		return $query;
	}

	public function getContactsCountQuery() {

		// find all the persons for this customer
		$query = "SELECT count( * )
				  FROM person 
				  INNER JOIN phone 
				  ON phone.personid = person.id 
				  WHERE person.deleted = 0 ";

		return $query;
	}

	public function fetchConsentFromContacts( $contacts ) {
		global $grapiClient;

		$phones = array();

		foreach( $contacts as $contact ) {
			$phones[] = $contact['phone'];
		}
		
		$uniquePhones = array_values( array_unique( $phones ) );

		$consentMetadata = $grapiClient->getDestinationMetaData( $uniquePhones ) ;

		return $consentMetadata;
	} 	

	public function mergeContactsWithConsent( $contacts, $consentMetadatas ) {

		$phones = array();

		$mergedData = array();

		foreach( $contacts as $contactData ) {

			$combinedData = array (
				"pkey" =>		$contactData["pkey"],
				"first_name" =>	$contactData["first_name"],
				"last_name" =>	$contactData["last_name"],
				"phone" => 		$contactData["phone"],
				"consent" => 	'unknown',
				"timestamp" =>  null,
			);

			foreach( $consentMetadatas as $metadata ) {
				if( $contactData['phone'] === $metadata->destination ) {

					$combinedData["consent"] =	$metadata->consent->call;
					$combinedData["timestamp"] = $metadata->createdTimestampMs;
				}
			}

			$mergedData[] = $combinedData;
		}
		
		return $mergedData;
	} 
}

?>
