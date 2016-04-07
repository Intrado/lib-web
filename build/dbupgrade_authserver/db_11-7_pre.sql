-- $rev 1

TRUNCATE TABLE loginattempt
$$$

ALTER TABLE loginattempt MODIFY login VARCHAR(255)
$$$


-- $rev 2
ALTER TABLE dbupgradehost MODIFY dbname VARCHAR(64)
$$$

ALTER TABLE dbupgrade MODIFY id VARCHAR(64)
$$$

TRUNCATE TABLE loginattempt
$$$

ALTER TABLE loginattempt MODIFY login VARCHAR(255) COLLATE utf8_general_ci
$$$



-- $rev 2
CREATE TABLE shortcodeshortcodegroup (
  id int(11) NOT NULL AUTO_INCREMENT,
  shortcode varchar(10) NOT NULL,
  shortcodegroupid int(11) not null,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8
$$$


INSERT INTO shortcodegroup (description, queuecapacity, numthreads, product, isdefault)
VALUES ('SchoolMessengerNG', 10000, 1, 'cs', 0)
$$$


INSERT INTO `shortcode` (`shortcode`, `smsaggregatorid`, `shortcodegroupid`)
SELECT '86088', a.`id`, g.`id` FROM `smsaggregator` AS a, `shortcodegroup` AS g
WHERE lower(a.`name`) = 'syniverse' AND lower(g.`description`) = 'schoolmessengerng'
$$$


INSERT INTO shortcodeareacode (shortcode, areacode)
VALUES ('86088', '')
$$$



INSERT INTO `shortcodetext` (`shortcode`, `messagetype`, `text`) VALUES
('86088','HELP','SchoolMessenger notifications: Reply STOP to cancel. Text Y to subscribe. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/mt for info'),
('86088','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('86088','OPTIN','You''re registered 4 SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/mt'),
('86088','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. HELP for help. Msg&data rates may apply. schoolmessenger.com/mt'),
('86088','PENDINGOPTIN','%s messages. Reply Y to confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/mt')
$$$



INSERT INTO shortcodegroup (description, queuecapacity, numthreads, product, isdefault)
VALUES ('GroupCast', 10000, 1, 'cs', 0)
$$$

INSERT INTO `shortcode` (`shortcode`, `smsaggregatorid`, `shortcodegroupid`)
SELECT '64779', a.`id`, g.`id` FROM `smsaggregator` AS a, `shortcodegroup` AS g
WHERE lower(a.`name`) = 'twilio' AND lower(g.`description`) = 'groupcast'
$$$


INSERT INTO shortcodeareacode (shortcode, areacode)
VALUES ('64779', '')
$$$


INSERT INTO `shortcodetext` (`shortcode`, `messagetype`, `text`) VALUES
('64779','HELP', 'GroupCast notification service: Reply Y to subscribe or STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit groupcast.com/txt for info'),
('64779','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('64779','OPTIN','You''re registered 4 GroupCast  notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. Msg freq varies. groupcast.com/txt'),
('64779','OPTOUT','You''re unsubscribed from GroupCast. No more msgs will be sent. Reply Y to re-subscribe, HELP for help. Msg&data rates may apply. groupcast.com/txt'),
('64779','PENDINGOPTIN','%s messages. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. groupcast.com/txt')
$$$

insert into shortcodeshortcodegroup (shortcode, shortcodegroupid) select distinct shortcode, shortcodegroupid from shortcode;
$$$


insert into shortcodeshortcodegroup (shortcode, shortcodegroupid) select '724665', id from shortcodegroup
where (lower(description) like '%twilio%') or (lower(description) = 'schoolmessengerng')
$$$

ALTER TABLE shortcode drop shortcodegroupid
$$$
