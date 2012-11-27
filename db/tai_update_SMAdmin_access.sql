
select @smaccessid:=id from access where name = 'SchoolMessenger Admin';
$$$
delete from permission where accessid = @smaccessid and name like 'tai_%';
$$$
INSERT INTO `permission` (accessid, name, value) VALUES 
						 (@smaccessid, 'tai_canforwardthread', '1'),
						 (@smaccessid, 'tai_canviewreports', '1'),
						 (@smaccessid, 'tai_canmanagenews', '1'),
						 (@smaccessid, 'tai_cansendanonymously', '1'),
						 (@smaccessid, 'tai_canmanagetopics', '1'),
						 (@smaccessid, 'tai_canbetopicrecipient', '1'),
						 (@smaccessid, 'tai_canusecannedresponses', '1'),
						 (@smaccessid, 'tai_canmanagecannedresponses', '1'),
						 (@smaccessid, 'tai_canrequestidentityreveal', '0'),
						 (@smaccessid, 'tai_canmanagesurveys', '10'),
						 (@smaccessid, 'tai_canmanagelockouts', '1'),
						 (@smaccessid, 'tai_canmanageactivationcodes', '1'),
						 (@smaccessid, 'tai_canmodifydisplayname', '1'),
						 (@smaccessid, 'tai_canviewunreadmessagereport', '1');
$$$
