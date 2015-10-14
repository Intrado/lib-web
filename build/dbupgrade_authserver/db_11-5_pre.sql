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
