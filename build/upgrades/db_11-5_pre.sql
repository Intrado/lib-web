-- $rev 1

ALTER TABLE `reportcontact`
 MODIFY COLUMN `result` ENUM('C', 'A', 'M', 'N', 'B', 'X', 'F', 'sent', 'unsent', 'printed', 'notprinted', 'notattempted', 'duplicate',
 'blocked', 'declined', 'queued', 'sending', 'delivered', 'undelivered', 'queueoverflow', 'accountsuspended',
 'unreachabledest', 'unknowndest', 'landline', 'carrierviolation', 'unknownerror')
 NOT NULL DEFAULT 'notattempted';
$$$

-- $rev 2

-- dummy, no change

-- $rev 3

ALTER TABLE `user` MODIFY COLUMN `login` VARCHAR(255)
$$$