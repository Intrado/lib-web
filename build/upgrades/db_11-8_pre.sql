-- $rev 1
-- no-op

-- $rev 2

-- NOTE: the base table in aspshard must be created before this view can reference it
CREATE VIEW smslanguage AS
  SELECT l.* FROM language AS l INNER JOIN aspshard.smslanguage AS sl USING (code)
$$$