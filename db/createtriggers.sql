-- create all triggers and stored procedures for an existing customer database

CREATE TRIGGER insert_repeating_job
AFTER INSERT ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;

IF NEW.status IN ('repeating') THEN
  SELECT value INTO tz FROM setting WHERE name='timezone';

  INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid)
         VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.smsmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, 'repeating', NEW.jobtypeid);

  -- copy the jobsettings
  INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;

  -- copy the joblists
  INSERT INTO aspshard.qjoblist (customerid, jobid, listid) SELECT custid, NEW.id, listid FROM joblist WHERE jobid=NEW.id;

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
    INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid)
           VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.smsmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, NEW.status, NEW.jobtypeid);
    -- copy the jobsettings
    INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;
    -- copy the joblists
    INSERT INTO aspshard.qjoblist (customerid, jobid, listid) SELECT custid, NEW.id, listid FROM joblist WHERE jobid=NEW.id;
  END IF;
ELSE
-- update job fields
  UPDATE aspshard.qjob SET scheduleid=NEW.scheduleid, phonemessageid=NEW.phonemessageid, emailmessageid=NEW.emailmessageid, printmessageid=NEW.printmessageid, smsmessageid=NEW.smsmessageid, questionnaireid=NEW.questionnaireid, starttime=NEW.starttime, endtime=NEW.endtime, startdate=NEW.startdate, enddate=NEW.enddate WHERE customerid=custid AND id=NEW.id;
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
DELETE FROM aspshard.qjobsetting WHERE customerid=custid AND jobid=OLD.id;
DELETE FROM aspshard.qjoblist WHERE customerid=custid AND jobid=OLD.id;
END
$$$

CREATE TRIGGER insert_jobsetting
AFTER INSERT ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DECLARE cc INTEGER;

-- the job must be inserted before the settings
SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND id=NEW.jobid;
IF cc = 1 THEN
    INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) VALUES (custid, NEW.jobid, NEW.name, NEW.value);
END IF;
END
$$$

CREATE TRIGGER update_jobsetting
AFTER UPDATE ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
UPDATE aspshard.qjobsetting SET value=NEW.value WHERE customerid=custid AND jobid=NEW.jobid AND name=NEW.name;
END
$$$

CREATE TRIGGER delete_jobsetting
AFTER DELETE ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DELETE FROM aspshard.qjobsetting WHERE customerid=custid AND jobid=OLD.jobid AND name=OLD.name;
END
$$$

CREATE TRIGGER insert_joblist
AFTER INSERT ON joblist FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DECLARE cc INTEGER;

-- the job must be inserted before the lists
SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND id=NEW.jobid;
IF cc = 1 THEN
    INSERT INTO aspshard.qjoblist (customerid, jobid, listid) VALUES (custid, NEW.jobid, NEW.listid);
END IF;
END
$$$

CREATE TRIGGER delete_joblist
AFTER DELETE ON joblist FOR EACH ROW
BEGIN
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;
DELETE FROM aspshard.qjoblist WHERE customerid=custid AND jobid=OLD.jobid AND listid=OLD.listid;
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
