-- $rev 1
-- no-op

-- $rev 2

-- NOTE: the base table in aspshard must be created before this view can reference it
CREATE OR REPLACE SQL SECURITY DEFINER VIEW smslanguage AS
  SELECT l.* FROM language AS l INNER JOIN aspshard.smslanguage AS sl USING (code)
$$$

-- $rev 3
ALTER TABLE messagepart MODIFY txt TEXT CHARSET utf8mb4
$$$
