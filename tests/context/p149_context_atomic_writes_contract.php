<?php
/**
 * Contract for atomic canonical Context group writes.
 *
 * The assertions against managegroups are deliberately method-scoped. Other
 * legacy methods remain for the subsequent LiveUser consumer migration and
 * must not be mistaken for the active Context-creation path.
 *
 * @author Derek Keats
 */

function methodSource($source, $methodName)
{
    $tokens = token_get_all($source);
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        $start = $i;
        for ($j = $i + 1; $j < $count; $j++) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                if ($tokens[$j][1] !== $methodName) {
                    break;
                }
                $depth = 0;
                $opened = false;
                $body = '';
                for ($k = $start; $k < $count; $k++) {
                    $token = $tokens[$k];
                    $text = is_array($token) ? $token[1] : $token;
                    $body .= $text;
                    if (!is_array($token) && $token === '{') {
                        $depth++;
                        $opened = true;
                    } elseif (!is_array($token) && $token === '}') {
                        $depth--;
                        if ($opened && $depth === 0) {
                            return $body;
                        }
                    }
                }
                break;
            }
            if (!is_array($tokens[$j]) && $tokens[$j] === '(') {
                break;
            }
        }
    }
    return false;
}

$root = dirname(__DIR__, 2) . '/app/core_modules/';
$manage = file_get_contents($root . 'contextgroups/classes/managegroups_class_inc.php');
$service = file_get_contents($root . 'groupadmin/classes/groupservice_class_inc.php');
$membership = file_get_contents($root . 'groupadmin/classes/groupmembershipdb_class_inc.php');
if ($manage === false || $service === false || $membership === false) {
    fwrite(STDERR, "FAIL: unable to read p149 targets\n");
    exit(1);
}

$init = methodSource($manage, 'init');
$addGroupMembers = methodSource($manage, 'addGroupMembers');
if ($init === false || $addGroupMembers === false) {
    fwrite(STDERR, "FAIL: unable to isolate active Context-creation methods\n");
    exit(1);
}

$required = array(
    array($init, '$this->currentUser = $this->_objUser->userId();'),
    array($addGroupMembers, '$objGroupService->ensureMembership('),
    array($service, 'INSERT INTO tbl_perms_group_subgroups'),
    array($service, '$this->objMembershipDb->ensureMembership('),
    array($membership, 'INSERT INTO tbl_perms_groupusers'),
);
foreach ($required as $contract) {
    if (strpos($contract[0], $contract[1]) === false) {
        fwrite(STDERR, "FAIL: missing p149 canonical write contract\n");
        exit(1);
    }
}

$forbidden = array(
    array($init, '$this->currentUser = $this->_objUser->PKId();'),
    array($addGroupMembers, '$this->_objGroupAdmin->addGroupUser('),
    array($service, '$this->objGroups->assignCanonicalSubGroup('),
);
foreach ($forbidden as $contract) {
    if (strpos($contract[0], $contract[1]) !== false) {
        fwrite(STDERR, "FAIL: legacy write remains in active Context-creation path\n");
        exit(1);
    }
}

echo "PASS: active Context hierarchy and membership writes use canonical dbTable paths.\n";
