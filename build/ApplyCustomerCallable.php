<?
/**
 * Enforces a contract which is used when running apply_customer_php.php
 *
 * This Interface contains a call method which is called by the apply script.
 * Implementers of this interface MUST implement call() and have a constructor which
 * accepts a customer database connection object.
 *
 * User: nrheckman
 * Date: 8/7/14
 * Time: 11:07 AM
 */

interface ApplyCustomerCallable {
	public function call();
}
?>