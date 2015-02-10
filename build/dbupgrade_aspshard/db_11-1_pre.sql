-- $rev 1

select 1
$$$

-- $rev 2

-- JobPeopleProcessor needs to know the recipients for each target in the lists of the job. this info lies in the JobProcessor
alter table qjobperson
add recipientpersonid int,
drop primary key,
add primary key (customerid, jobid, personid, recipientpersonid)
$$$


