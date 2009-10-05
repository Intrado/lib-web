-- Delete Jobs older than given date
-- Removes old job entries

DELETE 	j
FROM	job j
WHERE	j.finishdate < '1970-01-01'
AND		j.status in ('complete','cancelled')
$$$

-- Delete joblanguage
-- remove joblanguage without a job

DELETE	jl
FROM 	joblanguage jl
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.id = jl.jobid)
$$$

-- Delete joblist
-- remove joblist without a job

DELETE	jl
FROM 	joblist jl
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.id = jl.jobid)
$$$

-- Delete jobsetting
-- remove jobsetting without a job

DELETE	js
FROM 	jobsetting js
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.id = js.jobid)
$$$

-- Delete survey templates
-- Remove survey templates not associated with a job and deleted.

DELETE	sq
FROM	surveyquestionnaire sq
WHERE	sq.deleted
AND		not exists (
		SELECT 	*
		FROM	job j
		WHERE	j.questionnaireid = sq.id)
$$$

-- Delete surveyquestion entries
-- Remove survey question records not associated with a survey questionnaire

DELETE	s
FROM	surveyquestion s
WHERE	not exists (
		SELECT	*
		FROM	surveyquestionnaire sq
		WHERE	sq.id = s.questionnaireid)
$$$

-- Delete surveyresponse 
-- Remove survey responses for jobs that don't exist

DELETE	sr
FROM	surveyresponse sr
WHERE	not exists (
		SELECT	*
		FROM	job j
		WHERE	j.id = sr.jobid)
$$$


-- Delete reportperson 
-- Remove report person entries not associated with a job

DELETE	rp
FROM	reportperson rp
WHERE	not exists (
		SELECT	*
		FROM	job j
		WHERE	j.id = rp.jobid)	
$$$

-- Delete reportcontact
-- Remove reportcontact entries that are not associated with a job.

DELETE 	rc
FROM	reportcontact rc
WHERE	not exists (
		SELECT 	*
		FROM	job j
		WHERE	j.id = rc.jobid)
$$$

-- Delete Messages
-- Remove all messages that are deleted and not associated with any job.
-- Remove message parts for deleted messages also

DELETE	m
FROM	message m
WHERE	m.deleted
AND		not exists (
		SELECT 	*
		FROM 	job j
		WHERE	j.phonemessageid = m.id or
				j.emailmessageid = m.id or
				j.printmessageid = m.id or
				j.smsmessageid = m.id)
AND		not exists (
		SELECT 	*
		FROM 	joblanguage jl
		WHERE	jl.messageid = m.id)
AND		not exists (
		SELECT	*
		FROM	surveyquestion sq
		WHERE	sq.phonemessageid = m.id)
AND		not exists (
		SELECT 	*
		FROM	surveyquestionnaire sq
		WHERE	sq.machinemessageid = m.id or
				sq.emailmessageid = m.id or
				sq.intromessageid = m.id or
				sq.exitmessageid = m.id)
$$$

-- Delete Messages
-- Remove all messages not used since a specific date and not reference by any existing jobs.

DELETE	m
FROM	message m
WHERE	m.lastused < '1970-01-01'
AND		not exists (
		SELECT 	*
		FROM 	job j
		WHERE	j.phonemessageid = m.id or
				j.emailmessageid = m.id or
				j.printmessageid = m.id or
				j.smsmessageid = m.id)
AND		not exists (
		SELECT 	*
		FROM 	joblanguage jl
		WHERE	jl.messageid = m.id)
AND		not exists (
		SELECT	*
		FROM	surveyquestion sq
		WHERE	sq.phonemessageid = m.id)
AND		not exists (
		SELECT 	*
		FROM	surveyquestionnaire sq
		WHERE	sq.machinemessageid = m.id or
				sq.emailmessageid = m.id or
				sq.intromessageid = m.id or
				sq.exitmessageid = m.id)
$$$

-- Delete message parts
-- Remove messageparts that are not associated with a message

DELETE	mp
FROM	messagepart mp
WHERE	not exists (
		SELECT	*
		FROM	message m
		WHERE 	m.id = mp.messageid)
$$$

-- Audiofiles for deletion
-- Remove audiofiles that are not associated with any message parts and are deleted.

DELETE	a
FROM	audiofile a
WHERE	a.deleted
and		not exists (
		SELECT	*
		FROM	messagepart mp
		WHERE	mp.audiofileid = a.id)
$$$

-- Audiofiles for deletion
-- Remove audiofiles that are not associated with any message parts and are
-- before specified record date.

DELETE	a
FROM	audiofile a
WHERE	a.recorddate < '1970-01-01'
and		not exists (
		SELECT	*
		FROM	messagepart mp
		WHERE	mp.audiofileid = a.id)
$$$

-- Email message attachments for deletion
-- Deleted email attachments that are not associated with any messages

DELETE	ma
FROM	messageattachment ma
WHERE	not exists (
		SELECT	*
		FROM	message m
		WHERE	ma.messageid = m.id)
$$$

-- Content deletion
-- Remove all content that is not associated with an audiofile, message attachment, voicereply or logo/login

DELETE	c
FROM	content c
WHERE	not exists (
		SELECT	*
		FROM	audiofile a
		WHERE	a.contentid = c.id)
and 	not exists (
		SELECT	*
		FROM	messageattachment ma
		WHERE	ma.contentid = c.id)
and		not exists (
		SELECT	*
		FROM	voicereply vr
		WHERE	vr.contentid = c.id)
and		not exists (
		SELECT *
		FROM	setting
		where name = '_logocontentid'
		and value = c.id)
and		not exists (
		SELECT *
		FROM	setting
		where name = '_loginpicturecontentid'
		and value = c.id)
$$$

-- List deletion
-- Remove all lists that are not associated with a job and are deleted

DELETE	l
FROM	list l
WHERE	l.deleted
and		not exists (
		SELECT 	*
		FROM 	joblist jl
		WHERE 	jl.listid = l.id)

$$$

-- List entry removal
-- Remove all list entries that are not associated with a list, person or rule

DELETE	le
FROM	listentry le
WHERE	not exists (
		SELECT 	*
		FROM 	list l
		WHERE 	l.id = le.listid)
or		(le.personid is not null
		and not exists (
		SELECT	*
		FROM	person p
		WHERE	p.id = le.personid))
or		(le.ruleid is not null
		and	not exists (
		SELECT	*
		FROM	rule r
		WHERE	r.id = le.ruleid))
$$$

-- List import removal
-- Remove list imports for deleted lists

DELETE 	i
FROM 	import i
WHERE 	i.type = 'list'
and		not exists (
		SELECT 	*
		FROM 	list l
		WHERE 	l.id = i.listid)
$$$

-- Delete persons
-- Remove persons who have not been changed in more than six months and are deleted

DELETE 	p
FROM	person p
WHERE	p.lastimport < (now() - interval 6 month)
and 	p.deleted
$$$

-- Delete Contactpref entries
-- Remove contact preferences for people who don't exist

DELETE	cp
FROM	contactpref cp
WHERE	not exists (
		SELECT 	*
		FROM	person p
		WHERE	p.id = cp.personid)
$$$

-- Delete portalpersontokens
-- Remove portal tokens for persons that don't exist

DELETE	ppt
FROM	portalpersontoken ppt
WHERE	not exists (
		SELECT	*
		FROM	person p
		WHERE	p.id = ppt.personid)
$$$

-- Delete portalpersons
-- Remove portal person records for persons that don't exist

DELETE	pp
FROM	portalperson pp
WHERE	not exists (
		SELECT	*
		FROM	person p
		WHERE	p.id = pp.personid)
$$$

-- Delete address entries
-- Remove addresses for people who are no longer in the person table.

DELETE	a
FROM	address a
WHERE	not exists (
		SELECT 	*
		FROM	person p
		WHERE	p.id = a.personid)
$$$

-- Delete import fields
-- Remove import fields that are associated with imports that were removed due to deleted list removal

DELETE	imf
FROM	importfield imf
WHERE	not exists (
		SELECT 	*
		FROM 	import i
		WHERE	i.id = imf.importid)
$$$

-- Delete phone
-- remove phone records not associated with any person

DELETE	ph
FROM 	phone ph
WHERE	not exists (
		SELECT *
		FROM	person p
		WHERE	p.id = ph.personid)
$$$

-- Delete email
-- remove email records not associated with any person

DELETE	e
FROM 	email e
WHERE	not exists (
		SELECT *
		FROM	person p
		WHERE	p.id = e.personid)
$$$

-- Delete sms
-- remove sms records not associated with any person

DELETE	s
FROM 	sms s
WHERE	not exists (
		SELECT *
		FROM	person p
		WHERE	p.id = s.personid)
$$$

-- Delete rule
-- remove rules without a userrule or listentry

DELETE	r
FROM 	rule r
WHERE	not exists (
		SELECT *
		FROM	userrule ur
		WHERE	ur.ruleid = r.id)
and		not exists (
		SELECT *
		FROM	listentry le
		WHERE	le.ruleid = r.id)
$$$

-- Delete schedule
-- remove schedule without a job or import

DELETE	s
FROM 	schedule s
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.scheduleid = s.id)
and		not exists (
		SELECT *
		FROM	import i
		WHERE	i.scheduleid = s.id)
$$$


-- Delete specialtask
-- Remove completed special tasks

DELETE	st
FROM	specialtask st
WHERE	status = 'done'
$$$




