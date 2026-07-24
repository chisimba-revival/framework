-- Chisimba Reborn: canonical tbl_users identity constraints.
--
-- Before applying this migration to an existing installation, resolve every
-- duplicate id, userid and username deliberately. This migration intentionally
-- fails rather than silently deleting user records.

ALTER TABLE tbl_users
    ADD UNIQUE KEY uq_users_id (id),
    ADD UNIQUE KEY uq_users_userid (userid),
    ADD UNIQUE KEY uq_users_username (username);
