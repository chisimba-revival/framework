-- field_guiding_rights.sql — the permissions the field guiding API recognises.
--
-- This file inserts rows rather than creating a table, and that is the whole
-- point of it: the permission vocabulary has to live in Chisimba's own rights
-- tables, beside the rights Chisimba already enforces, or the microservice ends
-- up with a second answer free to disagree with the first. A group would be in
-- tbl_perms_groups and hold a right held here; the microservice reads the join
-- and never has to be told what a group means.
--
-- Why this was necessary, stated so it is not undone:
--
-- The token's scope claim used to carry GROUP NAMES. 'scope' and 'roles' were
-- the same array, and the microservice's required scope was configured as
-- FIELDSVC_REQUIRED_SCOPE='Site Admin' — which did not mean "may write data", it
-- meant "may write data if you are in a group whose name is Site Admin". A
-- permission was expressed as a group membership, and one string was doing three
-- jobs: the context code, the role, and the permission. Adding one ability meant
-- inventing a group name and hardcoding it in a Go constant.
--
-- A permission is a permission. It belongs in the rights table, it says what may
-- be done, and the token carries it so the bearer — or anyone reading a captured
-- token — can see it without a database.
--
-- ---------------------------------------------------------------------------
-- Deterministic identifiers
--
-- The id is derived from the name rather than generated. Chisimba's own rows use
-- perm_ plus random hex, which is fine for rows created once by hand; for a file
-- that may be applied more than once, a derived id means re-running it inserts
-- nothing rather than inserting a second right under a second id with the same
-- name. Two rows claiming to be the same permission is a question with no answer,
-- and this is the same reasoning as deriving the signing key's kid from its
-- modulus: no second record that can disagree with the first.
--
-- The area is created rather than reused. rights.view belongs to Chisimba's own
-- administration, and filing a field-guiding permission under that area would
-- say a trainee's data access is a form of user administration.

-- The area field-guiding permissions live under.
--
-- The area row carries the same four things Chisimba's own does: a derived id,
-- the owning application, the area key, and the display name. application_id is
-- what makes the ownership explicit — a right under the 'chisimba' application
-- is one Chisimba itself administers.
insert into tbl_perms_areas (puid, id, application_id, area_id, area_define_name)
select n.next_puid,
       concat('perm_', substr(md5('area:fieldguiding'), 1, 24)),
       'chisimba',
       n.next_puid,
       'Field guiding'
from (select coalesce(max(puid), 0) + 1 as next_puid from tbl_perms_areas) as n
where not exists (select 1 from tbl_perms_areas where area_define_name = 'Field guiding');

-- The rights themselves.
--
--   logbook.write     record and correct a log book entry
--   logbook.verify    answer a claim — the service door Chisimba's mentor module
--                     uses, formerly the separate field:verify scope, folded in so
--                     there is ONE vocabulary rather than two
--   signoff.submit    put a sign-off forward for review
--   signoff.review    approve, reject, or request changes
--   signoff.read      read a sign-off beyond one's own
--   outing.write      create and change an outing
--   media.write       attach media to an outing or entry
--   species.read      read the species catalogue
--
-- Deliberately NOT seeded against any group in this file. Granting is an
-- operator's decision, and a file that granted as it installed would hand a new
-- administrator every permission in the list the first time it ran.
--
-- area_id is the area's own puid, which is how Chisimba's own rows are shaped and
-- what the join the microservice performs relies on. max(puid) + row_number() is
-- not safe under concurrent inserts; this file is applied once by an operator,
-- and the not-exists guard means a second application adds nothing.
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'logbook.write')), 1, 24)),
       a.puid, 'logbook.write', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'logbook.write');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'logbook.verify')), 1, 24)),
       a.puid, 'logbook.verify', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'logbook.verify');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'signoff.submit')), 1, 24)),
       a.puid, 'signoff.submit', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'signoff.submit');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'signoff.review')), 1, 24)),
       a.puid, 'signoff.review', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'signoff.review');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'signoff.read')), 1, 24)),
       a.puid, 'signoff.read', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'signoff.read');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'outing.write')), 1, 24)),
       a.puid, 'outing.write', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'outing.write');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'media.write')), 1, 24)),
       a.puid, 'media.write', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'media.write');
insert into tbl_perms_rights (puid, id, area_id, right_define_name, has_implied)
select (select coalesce(max(puid), 0) + 1 from tbl_perms_rights),
       concat('perm_', substr(md5(concat('right:', 'species.read')), 1, 24)),
       a.puid, 'species.read', NULL
from (select puid from tbl_perms_areas where area_define_name = 'Field guiding') as a
where not exists (select 1 from tbl_perms_rights e where e.right_define_name = 'species.read');
-- One statement per right rather than a single set-based insert, which is a
-- consequence of the server rather than a preference: this is MariaDB, and
-- MariaDB does not accept the column-alias form on a VALUES list, so the
-- eight-row insert has to be written as a UNION of derived tables to stay
-- set-based. Eight guarded statements say the same thing, are read by whoever
-- has to audit them, and run on either server.
