-- Chisimba Reborn canonical identity integrity migration.
-- Existing data must pass duplicate and orphan checks before this is applied.

ALTER TABLE tbl_perms_perm_users
    MODIFY auth_user_id VARCHAR(25) NOT NULL;

ALTER TABLE tbl_perms_perm_users
    ADD UNIQUE KEY uq_perm_users_perm_user_id (perm_user_id),
    ADD UNIQUE KEY uq_perm_users_auth_identity
        (auth_container_name, auth_user_id);

ALTER TABLE tbl_perms_groupusers
    ADD UNIQUE KEY uq_group_membership (group_id, perm_user_id);
