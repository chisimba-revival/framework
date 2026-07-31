<?php
/**
 * Plain-text output for the bounded canonical-services proof.
 *
 * @category  Chisimba
 * @package   modulecatalogue
 * @author    Derek Keats
 */
header('Content-Type: text/plain; charset=UTF-8');
$p = isset($canonicalIdentityProof) && is_array($canonicalIdentityProof)
    ? $canonicalIdentityProof : array();
$v = function ($key) use ($p) {
    if (!array_key_exists($key, $p) || $p[$key] === null) return 'NULL';
    if (is_bool($p[$key])) return $p[$key] ? 'true' : 'false';
    return str_replace(array("\r", "\n"), '', (string) $p[$key]);
};
echo "CHISIMBA CANONICAL SERVICES CHAIN\n";
foreach (array(
    'ok', 'code', 'authenticated', 'user_id', 'permission_user_id',
    'username', 'display_name', 'identity_adapter_match',
    'site_admin_group_id', 'canonical_site_admin_member',
    'adapter_site_admin_member', 'membership_adapter_match',
    'area_id', 'right_id', 'modulecatalogue_toolbar_access',
    'legacy_is_admin'
) as $key) {
    echo $key . ': ' . $v($key) . "\n";
}
echo "groups:\n";
if (isset($p['groups']) && is_array($p['groups'])) {
    foreach ($p['groups'] as $group) {
        echo '  - ' . $group['id'] . ': ' . $group['name'] . "\n";
    }
}
echo "\nINTERPRETATION\n";
echo "modulecatalogue_toolbar_access is a real canonical stored right.\n";
echo "legacy_is_admin is reported separately; it is not relabelled as that right.\n";
?>
