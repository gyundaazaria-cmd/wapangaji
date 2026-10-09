USE gyunda_house;

UPDATE tenant_portal_invites
SET used_at = COALESCE(used_at, NOW())
WHERE expires_at <= NOW() AND used_at IS NULL;

ALTER TABLE tenant_portal_invites
    DROP INDEX idx_tenant_portal_invites_expiry,
    DROP COLUMN expires_at;
