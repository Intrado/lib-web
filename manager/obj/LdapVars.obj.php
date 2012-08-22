<?
// Object to store variables for LDAP configuration
// Different Attributes are used by different LDAP vendors.
// For example, Active Directory uses "userPrincipalName", whereas RedHat Directory Server uses "uid"

class LdapVars {
	var $usernameAttributeName; // attribute name to compare username against
	var $accountAttributeName;  // attribute name to check if user enabled
	var $useFQDN; // true for name@domain.com or false for just the name
	var $enabledOperation; // "bitcompare" or "stringcompare"
	var $ou; // name of the "ou" field in the customer ldap system (such as "employees") used only by RedHat
}
?>

