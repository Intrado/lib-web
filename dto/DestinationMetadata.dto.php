<?php

/**
 * Destination Metadata DTO
 *
 * This is currently known as an "endpoint" under Global Registry API; hopefully we can change that.
 */
class DestinationMetadata {

	/**
	 * integer (note: this might be REALLY big some day, so watch out for breaking PHP int limits)
	 */
	public $id;

	/**
	 * string: '\d{10}'|'email@address.com'
	 */
	public $destination;

	/**
	 * string: 'PHONE'|'EMAIL'|'DEVICE'
	 */
	public $type;

	/**
	 * Date this record was created (read only)
	 */
	public $createdDate;

	/**
	 * Date this record was last modified (read only)
	 */
	public $modifiedDate;

	/**
	 * Additional type information about this destination (readonly)
	 */
	public $subtype;

	/**
	 * constructor
	 *
	 * @param $type string 'PHONE'|'EMAIL'|'DEVICE'
	 * @param $destination string '\d{10}'|'email@address.com'
	 * @param $id integer (optional; may get REALLY big some day, so watch out for breaking PHP int limits)
	 */
	public function __construct($type, $destination, $id = null) {
		$this->type = $type;
		$this->destination = $destination;
		$this->id = $id;
		$this->block = new stdClass();
		$this->block->call = false;	// boolean
		$this->block->sms = false;	// boolean
		$this->consent = new stdClass();
		$this->consent->call = null;	// string: 'YES'|'NO'|'PENDING'
		$this->consent->sms = null;	// string: 'YES'|'NO'|'PENDING'
	}
}

