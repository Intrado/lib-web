-- $rev 1

ALTER TABLE `smsinbound` MODIFY COLUMN `carrier` VARCHAR(100) NOT NULL
$$$

-- $rev 2

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
