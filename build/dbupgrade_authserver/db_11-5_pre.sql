-- $rev 1

ALTER TABLE `smsinbound`
  MODIFY COLUMN `message_id` VARCHAR(100) NOT NULL
$$$

-- $rev 2

INSERT INTO smsaggregator (name) VALUES ('twilio')
$$$

INSERT INTO shortcodegroup (description, queuecapacity, numthreads, product, isdefault)
VALUES ('twilio', 10000, 1, 'cs', 1)
$$$

INSERT INTO shortcode (shortcode, smsaggregatorid, shortcodegroupid)
  SELECT '67587', sa.id, scg.id FROM smsaggregator sa JOIN shortcodegroup scg
    ON (sa.name = scg.description)
  WHERE sa.name = 'twilio'
$$$

INSERT INTO shortcodeareacode (shortcode, areacode)
VALUES ('67587', '')
$$$

INSERT INTO shortcodetext (shortcode, messagetype, text)
  SELECT '67587', messagetype, text FROM shortcodetext WHERE shortcode = '68453'
$$$

-- $rev 3

ALTER TABLE dmgroup ADD COLUMN carrier VARCHAR(50), ADD COLUMN state CHAR(2)
$$$

INSERT INTO dmgroup (id, carrier, state) VALUES
(1,'bandwidth','ca'),
(2,'xo','ca'),
(3,'xo','va'),
(4,'level3','il'),
(5,'xo','il'),
(6,'qwest','ca'),
(7,'simple','ca'),
(8,'level3','ca'),
(9,'xohvod','il'),
(10,'xohvod','ca'),
(11,'xohvod','va'),
(12,'centurylinktdm','il'),
(13,'centurylinktdm','il'),
(14,'centurylinktdm','ca'),
(15,'centurylinktdm','ca'),
(16,'centurylinktdm','va'),
(17,'centurylinktdm','va'),
(18,'centurylinkvoip','ca'),
(19,'centurylinkvoip','va'),
(20,'hypercube','il'),
(21,'bandwidthrated','ca'),
(22,'bandwidthrated','va'),
(23,'bandwidth','ca'),
(24,'xo','ca'),
(25,'xo','va'),
(26,'level3','il'),
(27,'xo','il'),
(28,'qwest','ca'),
(29,'simple','ca'),
(30,'level3','ca'),
(31,'xohvod','il'),
(32,'xohvod','ca'),
(33,'xohvod','va'),
(34,'centurylinktdm','il'),
(35,'centurylinktdm','il'),
(36,'centurylinktdm','ca'),
(37,'centurylinktdm','ca'),
(38,'centurylinktdm','va'),
(39,'centurylinktdm','va'),
(40,'centurylinkvoip','ca'),
(41,'centurylinkvoip','va'),
(42,'hypercube','il'),
(43,'bandwidthrated','ca'),
(44,'bandwidthrated','va')
ON DUPLICATE KEY UPDATE carrier=VALUES(carrier), state=VALUES(state)
$$$

-- $rev 4

INSERT INTO `shortcodetext` (`shortcode`, `messagetype`, `text`) VALUES
('67587','HELP','SchoolMessenger notification service: Reply Y to subscribe or STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/tm for info'),
('67587','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('67587','OPTIN','You''re registered 4 SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/tm'),
('67587','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. Reply Y to re-subscribe, HELP for help. Msg&data rates may apply. schoolmessenger.com/tm'),
('67587','PENDINGOPTIN','<district display name, max 50chars> alerts. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/tm'),
('68453','HELP','SchoolMessenger notification service: Reply Y to subscribe or STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/tm for info'),
('68453','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('68453','OPTIN','You''re registered 4 SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/tm'),
('68453','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. Reply Y to re-subscribe, HELP for help. Msg&data rates may apply. schoolmessenger.com/tm'),
('68453','PENDINGOPTIN','<district display name, max 50chars> alerts. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/tm'),
('724665','HELP','Text messages by SchoolMessenger. Reply Y to confirm, STOP to quit. Std msg/data rates apply. Msg freq varies schoolmessenger.com/tm'),
('724665','INFO','Unknown response. Reply Y to subscribe, STOP to quit, HELP for info. Std msg/data rates apply. Msg freq varies. schoolmessenger.com/tm'),
('724665','OPTIN','You''re registered 4 SchoolMessenger. Txt STOP to quit, HELP for help. Std msg/data rates apply. Freq varies. schoolmessenger.com/tm'),
('724665','OPTOUT','You''ve unsubscribed. Reply Y to subscribe, HELP for info. Std msg/data rates apply. Msg freq varies. More info at schoolmessenger.com/tm'),
('724665','PENDINGOPTIN','<district display name, max 50chars> alerts. Reply Y or HELP. Std msg/data rates apply. Freq varies. schoolmessenger.com/tm'),
('88544','HELP','SchoolMessenger notification service: Reply Y to subscribe or STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/fcs for info'),
('88544','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('88544','OPTIN','You''re registered 4 FCS SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/fcs'),
('88544','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. Reply Y to re-subscribe, Help for help. Msg&data rates may apply. schoolmessenger.com/fcs'),
('88544','PENDINGOPTIN','<district display name, max 50chars> alerts. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/fcs')
ON DUPLICATE KEY UPDATE `text` = VALUES(`text`)
$$$

-- $rev 5

INSERT INTO `shortcodetext` (`shortcode`, `messagetype`, `text`) VALUES
('67587','HELP','SchoolMessenger notification service: Reply Y to subscribe or STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/txt for info'),
('67587','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('67587','OPTIN','You''re registered 4 SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/txt'),
('67587','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. Reply Y to re-subscribe, HELP for help. Msg&data rates may apply. schoolmessenger.com/txt'),
('67587','PENDINGOPTIN','%s alerts. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/txt'),
('68453','HELP','SchoolMessenger notification service: Reply STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/tm for info'),
('68453','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('68453','OPTIN','You''re registered 4 SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. msg freq varies. schoolmessenger.com/tm'),
('68453','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. Reply Y to re-subscribe, HELP for help. Msg&data rates may apply. schoolmessenger.com/tm'),
('68453','PENDINGOPTIN','%s alerts. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/tm'),
('724665','HELP','Text messages by SchoolMessenger. Reply STOP to quit. Std msg/data rates apply. Msg freq varies schoolmessenger.com/tm'),
('724665','INFO','Unknown response. Reply Y to subscribe, STOP to quit, HELP for info. Std msg/data rates apply. Msg freq varies. schoolmessenger.com/tm'),
('724665','OPTIN','You''re registered 4 SchoolMessenger. Txt STOP to quit, HELP for help. Std msg/data rates apply. Freq varies. schoolmessenger.com/tm'),
('724665','OPTOUT','You won''t receive any further messages. Reply Y to re-subscribe, HELP for info. Std msg/data rates apply schoolmessenger.com/tm'),
('724665','PENDINGOPTIN','%s alerts. Reply Y or HELP.Std msg/data rates apply.Freq varies. schoolmessenger.com/tm'),
('88544','HELP','SchoolMessenger notification service: Reply Y to subscribe or STOP to cancel. Msg&data rates may apply. Msg freq varies. Visit schoolmessenger.com/fcs for info'),
('88544','INFO','Unknown response. Reply Y to subscribe. Text STOP to quit. Msg freq varies. For more information reply HELP.'),
('88544','OPTIN','You''re registered 4 FCS SchoolMessenger notifications. Reply STOP to cancel, HELP for help. Msg&data rates may apply. msg freq varies. schoolmessenger.com/fcs'),
('88544','OPTOUT','You''re unsubscribed from SchoolMessenger. No more msgs will be sent. Reply Y to re-subscribe, Help for help. Msg&data rates may apply. schoolmessenger.com/fcs'),
('88544','PENDINGOPTIN','%s alerts. Reply Y 2 confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/fcs')
ON DUPLICATE KEY UPDATE `text` = VALUES(`text`)
$$$
