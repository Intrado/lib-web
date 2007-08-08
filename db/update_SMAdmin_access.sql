
select @smaccessid:=id from access where name = 'SchoolMessenger Admin';
$$$
delete from permission where accessid = @smaccessid;
$$$
INSERT INTO `permission` (accessid,name,value) VALUES 
						 (@smaccessid, 'loginweb', '1'),
						 (@smaccessid, 'manageprofile', '1'),
						 (@smaccessid, 'manageaccount', '1'),
						 (@smaccessid, 'managesystem', '1'),
						 (@smaccessid, 'loginphone', '1'),
						 (@smaccessid, 'startstats', '1'),
						 (@smaccessid, 'startshort', '1'),
						 (@smaccessid, 'starteasy', '1'),
						 (@smaccessid, 'sendprint', '0'),
						 (@smaccessid, 'callmax', '10'),
						 (@smaccessid, 'sendemail', '1'),
						 (@smaccessid, 'sendphone', '1'),
						 (@smaccessid, 'sendsms', '1'),
						 (@smaccessid, 'sendmulti', '1'),
						 (@smaccessid, 'leavemessage', '1'),
						 (@smaccessid, 'survey', '1'),
						 (@smaccessid, 'createlist', '1'),
						 (@smaccessid, 'createrepeat', '1'),
						 (@smaccessid, 'createreport', '1'),
						 (@smaccessid, 'maxjobdays', '7'),
						 (@smaccessid, 'viewsystemreports', '1'),
						 (@smaccessid, 'viewusagestats', '1'),
						 (@smaccessid, 'viewcalldistribution', '1'),
						 (@smaccessid, 'managesystemjobs', '1'),
						 (@smaccessid, 'managemyaccount', '1'),
						 (@smaccessid, 'viewcontacts', '1'),
						 (@smaccessid, 'viewsystemactive', '1'),
						 (@smaccessid, 'viewsystemrepeating', '1'),
						 (@smaccessid, 'viewsystemcompleted', '1'),
						 (@smaccessid, 'listuploadids', '1'),
						 (@smaccessid, 'listuploadcontacts', '1'),
						 (@smaccessid, 'setcallerid', '1'),
						 (@smaccessid, 'blocknumbers', '1'),
						 (@smaccessid, 'callblockingperms', 'editall'),
						 (@smaccessid, 'metadata', '1'),
						 (@smaccessid, 'managetasks', '1');
$$$
						