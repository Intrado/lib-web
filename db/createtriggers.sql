-- create all triggers and stored procedures for an existing customer database

CREATE TRIGGER insert_repeating_job
AFTER INSERT ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;

IF NEW.status IN ('repeating') THEN
  SELECT value INTO tz FROM setting WHERE name='timezone';

  INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, messagegroupid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status)
         VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.messagegroupid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, 'repeating');

  -- do not copy schedule because it was inserted via the insert_schedule trigger

END IF;
END
$$$


CREATE TRIGGER update_job
AFTER UPDATE ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;

SELECT value INTO tz FROM setting WHERE name='timezone';

SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND id=NEW.id;
IF cc = 0 THEN
-- we expect the status to be 'scheduled' when we insert the shard job
-- status 'new' is for jobs that are not yet submitted
  IF NEW.status='scheduled' THEN
    INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, messagegroupid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status)
           VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.messagegroupid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, NEW.status);
  END IF;
ELSE
-- update job fields
  UPDATE aspshard.qjob SET scheduleid=NEW.scheduleid, messagegroupid=NEW.messagegroupid, questionnaireid=NEW.questionnaireid, starttime=NEW.starttime, endtime=NEW.endtime, startdate=NEW.startdate, enddate=NEW.enddate WHERE customerid=custid AND id=NEW.id;
  IF NEW.status IN ('processing', 'procactive', 'active', 'cancelling') THEN
    UPDATE aspshard.qjob SET status=NEW.status WHERE customerid=custid AND id=NEW.id;
  END IF;
END IF;
END
$$$


CREATE TRIGGER delete_job
AFTER DELETE ON job FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
-- only repeating jobs ever get deleted
DELETE FROM aspshard.qjob WHERE customerid=custid AND id=OLD.id;
END
$$$


CREATE TRIGGER insert_schedule
AFTER INSERT ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DECLARE tz VARCHAR(50);

SELECT value INTO tz FROM setting WHERE name='timezone';

INSERT INTO aspshard.qschedule (id, customerid, daysofweek, time, nextrun, timezone) VALUES (NEW.id, custid, NEW.daysofweek, NEW.time, NEW.nextrun, tz);
END
$$$

CREATE TRIGGER update_schedule
AFTER UPDATE ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
UPDATE aspshard.qschedule SET daysofweek=NEW.daysofweek, time=NEW.time, nextrun=NEW.nextrun WHERE id=NEW.id AND customerid=custid;
END
$$$

CREATE TRIGGER delete_schedule
AFTER DELETE ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DELETE FROM aspshard.qschedule WHERE id=OLD.id AND customerid=custid;
END
$$$

CREATE TRIGGER insert_reportsubscription
AFTER INSERT ON reportsubscription FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DECLARE tz VARCHAR(50);
SELECT value INTO tz FROM setting WHERE name='timezone';
INSERT INTO aspshard.qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) VALUES (NEW.id, custid, NEW.userid, NEW.type, NEW.daysofweek, NEW.dayofmonth, NEW.time, tz, NEW.nextrun, NEW.email);
END
$$$

CREATE TRIGGER update_reportsubscription
AFTER UPDATE ON reportsubscription FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
UPDATE aspshard.qreportsubscription SET type=NEW.type, daysofweek=NEW.daysofweek, dayofmonth=NEW.dayofmonth, time=NEW.time, nextrun=NEW.nextrun, email=NEW.email WHERE id=NEW.id AND customerid=custid;
END
$$$

CREATE TRIGGER delete_reportsubscription
AFTER DELETE ON reportsubscription FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DELETE FROM aspshard.qreportsubscription WHERE id=OLD.id AND customerid=custid;
END
$$$

create procedure start_import( in_importid int)
begin
declare l_custid int DEFAULT _$CUSTOMERID_;
insert ignore into aspshard.importqueue (customerid,localimportid) values (l_custid,in_importid);
end
$$$

create procedure start_specialtask( in_specialtaskid int)
begin
declare l_custid int DEFAULT _$CUSTOMERID_;
declare l_type varchar(50);
DECLARE rdm VARCHAR(50);
DECLARE dtype VARCHAR(50) DEFAULT 'system';

select type from specialtask where id=in_specialtaskid into l_type;

SELECT value INTO rdm FROM setting WHERE name='_dmmethod';
IF rdm='hybrid' or rdm='cs' THEN
  SET dtype = 'customer';
END IF;

insert ignore into aspshard.specialtaskqueue (customerid,localspecialtaskid,type,dispatchtype) values (l_custid,in_specialtaskid,l_type,dtype);
end
$$$
