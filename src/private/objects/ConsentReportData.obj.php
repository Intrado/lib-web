<?php

require_once("inc/common.inc.php");

class ConsentReportData {

	// queries the customer db for all persons and their associated phone number.
	// - accepts a job ID to only return people contacted for that specific job
	// - this function should take paging into consideration for HTML results
	// - it should also be able to return ALL results for CSV download
	//
	// @params $customerId::String [$jobId::String, $pagingLowerBoundary::Int, $pagingUpperBoundary::Int]
	// @returns $contacts::Array (array of objects containg the person data and thei)
	public function generateGetContactsQuery ( $phoneNumber = null, $jobId = null ) {

		$preparedStatementArgs = array();

		// find all the persons for this customer
		$query = "SELECT DISTINCT 
						 person.pkey, 
						 person.id, 
						 person.f01, 
						 person.f02, 
						 phone.phone
				  FROM person
				  INNER JOIN phone ON phone.personid = person.id ";

		if( $jobId ) {
			$query .= "INNER JOIN reportcontact ON reportcontact.personid = person.id ";
		}

		$query .= "WHERE person.deleted = 0 ";

		if( $phoneNumber ) {
			$query .= "AND phone.phone = ? ";
		}

		if( $jobId ) {
			$query .= " AND reportcontact.jobid = ? 
						AND reportcontact.type = 'phone' 
					  	AND phone.phone IS NOT NULL ";
		}

		$query .= "ORDER by person.f02 ";

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
				"f01" =>		$contactData["f01"],
				"f02" =>		$contactData["f02"],
				"phone" => 		$contactData["phone"],
				"consent" => 	'pending',
				"timestamp" =>  null,
			);

			foreach( $consentMetadatas as $metadata ) {
				if( $contactData['phone'] === $metadata->destination ) {

					$combinedData["consent"] =	$metadata->consent->call;
					$combinedData["timestamp"] = $metadata->createdTimestampMs;
				}
			}

			if( trim( $contactData["phone"] ) !== '' ) {
				$mergedData[] = $combinedData;
			}
		}
		
		return $mergedData;
	} 
}

?>