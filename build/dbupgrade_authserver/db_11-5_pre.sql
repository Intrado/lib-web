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
