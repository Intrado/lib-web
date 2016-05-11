-- $rev 1
ALTER TABLE reportcontact CHANGE result result
ENUM('C','A','M','N','B','X','F','sent','unsent','printed','notprinted',
'notattempted','duplicate','blocked','declined','queued','sending',
'delivered','undelivered','queueoverflow','accountsuspended','unreachabledest',
'unknowndest','landline','carrierviolation','unknownerror',
'failed', 'carrierblocked', 'consentdenied', 'consentpending',
'bounced', 'opened', 'tempfail')
NOT NULL
$$$
