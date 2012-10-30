-- $rev 1
INSERT INTO permission (accessid, name, value) SELECT accessid, 'tai_canmodifydisplayname', 1 FROM permission WHERE name = 'tai_canbetopicrecipient' AND value = 1
$$$

