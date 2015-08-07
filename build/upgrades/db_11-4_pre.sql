-- $rev 1

ALTER TABLE content
  ADD COLUMN width INT NULL,
  ADD COLUMN height INT NULL,
  ADD COLUMN originalContentId BIGINT NULL
$$$
