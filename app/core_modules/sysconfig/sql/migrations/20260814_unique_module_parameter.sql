-- Enforce one canonical sysconfig value for each module and parameter name.
-- Existing duplicate values must be reconciled before this migration runs.

ALTER TABLE tbl_sysconfig_properties
    ADD UNIQUE KEY sysconfig_module_parameter (pmodule, pname);
