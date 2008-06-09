-- Messages for deletion
-- Remove all messages that are deleted and not associated with any job.
-- Remove message parts for deleted messages also

DELETE	m,
	mp
FROM	message m
	LEFT JOIN messagepart mp on
		(m.id = mp.messageid)
WHERE	m.deleted
and		not exists (
		SELECT 	*
		FROM 	job j
		WHERE	j.phonemessageid = m.id or
			j.emailmessageid = m.id or
			j.printmessageid = m.id or
			j.smsmessageid = m.id
	)
and		not exists (
		SELECT 	*
		FROM 	joblanguage jl
		WHERE	jl.messageid = m.id
	)

$$$

-- Audiofiles for deletion
-- Remove audiofiles that are not associated with any message parts and are deleted.

DELETE	a
FROM	audiofile a
WHERE	a.deleted
and		not exists (
		SELECT	*
		FROM	messagepart mp
		WHERE	mp.audiofileid = a.id
	)

$$$

-- Email message attachments for deletion
-- Deleted email attachments that are not associated with any messages

DELETE	ma
FROM	messageattachment ma
WHERE	not exists (
		SELECT	*
		FROM	message m
		WHERE	ma.messageid = m.id
	)

$$$

-- Content deletion
-- Remove all content that is not associated with an audiofile, message attachment, voicereply or logo/login

DELETE	c
FROM	content c
WHERE	not exists (
		SELECT	*
		FROM	audiofile a
		WHERE	a.contentid = c.id
	)
and 	not exists (
		SELECT	*
		FROM	messageattachment ma
		WHERE	ma.contentid = c.id
	)
and		not exists (
		SELECT	*
		FROM	voicereply vr
		WHERE	vr.contentid = c.id
	)
and		not exists (
		SELECT *
		FROM	setting
		where name = '_logocontentid'
		and value = c.id
	)
and		not exists (
		SELECT *
		FROM	setting
		where name = '_loginpicturecontentid'
		and value = c.id
	)
$$$

-- List deletion
-- Remove all lists that are not associated with a job and are deleted

DELETE	l
FROM	list l
WHERE	l.deleted
and		not exists (
		SELECT 	*
		FROM 	job j
		WHERE 	j.listid = l.id
	)

$$$

-- List entry removal
-- Remove all list entries that are not associated with a list, person or rule

DELETE	le
FROM	listentry le
WHERE	not exists (
		SELECT 	*
		FROM 	list l
		WHERE 	l.id = le.listid
	)
or		(le.personid is not null
		and not exists (
		SELECT	*
		FROM	person p
		WHERE	p.id = le.personid
		)
	)
or		(le.ruleid is not null
		and	not exists (
		SELECT	*
		FROM	rule r
		WHERE	r.id = le.ruleid
		)
	)

$$$

-- List import removal
-- Remove list imports for deleted lists

DELETE 	i
FROM 	import i
WHERE 	i.type = 'list'
and		not exists (
		SELECT 	*
		FROM 	list l
		WHERE 	l.id = i.listid
	)

$$$

-- Delete persons
-- Remove persons who have not been changed in more than six months and are deleted

DELETE 	p
FROM	person p
WHERE	p.lastimport < (now() - interval 6 month)
and 	p.deleted

$$$

-- Delete import fields
-- Remove import fields that are associated with imports that were removed due to deleted list removal

DELETE	imf
FROM	importfield imf
WHERE	not exists (
		SELECT 	*
		FROM 	import i
		WHERE	i.id = imf.importid
	)
$$$

-- Delete phone
-- remove phone records not associated with any person

DELETE	ph
FROM 	phone ph
WHERE	not exists (
		SELECT *
		FROM	person p
		WHERE	p.id = ph.personid
	)
$$$

-- Delete email
-- remove email records not associated with any person

DELETE	e
FROM 	email e
WHERE	not exists (
		SELECT *
		FROM	person p
		WHERE	p.id = e.personid
	)
$$$

-- Delete sms
-- remove sms records not associated with any person

DELETE	s
FROM 	sms s
WHERE	not exists (
		SELECT *
		FROM	person p
		WHERE	p.id = s.personid
	)
$$$

-- Delete joblanguage
-- remove joblanguage without a job

DELETE	jl
FROM 	joblanguage jl
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.id = jl.jobid
	)
$$$


-- Delete jobsetting
-- remove jobsetting without a job

DELETE	js
FROM 	jobsetting js
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.id = js.jobid
	)
$$$


-- Delete rule
-- remove rules without a userrule or listentry

DELETE	r
FROM 	rule r
WHERE	not exists (
		SELECT *
		FROM	userrule ur
		WHERE	ur.ruleid = r.id
	)
and		not exists (
		SELECT *
		FROM	listentry le
		WHERE	le.ruleid = r.id
	)

$$$

-- Delete schedule
-- remove schedule without a job or import

DELETE	s
FROM 	schedule s
WHERE	not exists (
		SELECT *
		FROM	job j
		WHERE	j.scheduleid = s.id
	)
and		not exists (
		SELECT *
		FROM	import i
		WHERE	i.scheduleid = s.id
	)

$$$


-- delete specialtask
delete from specialtask where status='done'
$$$




