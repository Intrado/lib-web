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
-- Remove all content that is not associated with an audiofile, message attachment or voicereply

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
-- Remove all list entries that are not associated with a list

DELETE	le
FROM	listentry le
WHERE	not exists (
		SELECT 	*
		FROM 	list l
		WHERE 	l.id = le.listid
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
