<?php

/* -------------------- prelogin class extends controller ---------------- */
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

/**
 * Provide the public site home page and its administrator block editor.
 *
 * @category  Chisimba
 * @package   prelogin
 * @author    Nic Appleby
 * @author    Derek Keats
 * @copyright 2006 UWC
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
class prelogin extends controller
{
    /**
     * Initialise the services used by the public page and editor.
     *
     * @return void
     */
    public function init()
    {
        $this->objModule = $this->getObject('modules', 'modulecatalogue');
        $this->objBlocks = $this->getObject('blocks', 'blocks');
        $this->objPLBlocks = $this->getObject('preloginblocks');
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objSysconfig = $this->getObject('dbsysconfig', 'sysconfig');
        $this->cbExists = $this->objModule->checkIfRegistered('contentblocks');
        if ($this->cbExists) {
            $this->objBlocksContent = $this->getObject(
                'dbcontentblocks',
                'contentblocks'
            );
        }

        if ($this->objPLBlocks->dbType === 'pgsql') {
            $this->TRUE = 't';
            $this->FALSE = 'f';
        } else {
            $this->TRUE = 1;
            $this->FALSE = 0;
        }
    }

    /**
     * Dispatch a public-page or block-editor action.
     *
     * @param string $action Requested controller action
     *
     * @return string Template name
     */
    public function dispatch($action)
    {
        switch ($action) {
            case 'admin':
                return $this->showPage(TRUE);
            case 'addregisteredblock':
                return $this->addRegisteredBlock();
            case 'moveup':
                return $this->movePlacedBlock('up');
            case 'movedown':
                return $this->movePlacedBlock('down');
            case 'delete':
                return $this->deletePlacedBlock();
            default:
                return $this->showPage(FALSE);
        }
    }

    /**
     * Render the public page, optionally with inline editing controls.
     *
     * @param boolean $editing TRUE to expose editing tools
     *
     * @return string Template filename
     */
    private function showPage($editing)
    {
        $isAdmin = $this->objUser->isAdmin();
        if ($editing && !$isAdmin) {
            return 'notadmin_tpl.php';
        }

        $this->setVar('preloginCanEdit', $isAdmin);
        $this->setVar('preloginEditing', $editing && $isAdmin);
        if ($editing && $isAdmin) {
            $smallBlocks = $this->getBlockCatalogue('normal');
            $wideBlocks = $this->getBlockCatalogue('wide');
            $this->setVarByRef('smallBlocks', $smallBlocks);
            $this->setVarByRef('wideBlocks', $wideBlocks);
        }

        $this->setVar('pageSuppressToolbar', TRUE);
        $this->setLayoutTemplate(NULL);
        return 'prelogin_tpl.php';
    }

    /**
     * Add a selected registered block to the public-page layout.
     *
     * @return string Template filename after redirect
     */
    private function addRegisteredBlock()
    {
        if (!$this->objUser->isAdmin()) {
            return 'notadmin_tpl.php';
        }

        $side = (string) $this->getParam('side', '');
        $descriptor = (string) $this->getParam('blockid', '');
        $block = $this->findCatalogueBlock($descriptor, $side);
        if ($block === FALSE) {
            return $this->nextAction(
                'admin',
                array('catalogueerror' => '1')
            );
        }

        $this->objPLBlocks->insertBlock(array(
            'title' => $block['displaytitle'],
            'side' => $side,
            'content' => '',
            'isblock' => $this->TRUE,
            'blockname' => $block['blockname'],
            'blockmodule' => $block['moduleid'],
        ));
        return $this->nextAction('admin', array('change' => '2'));
    }

    /**
     * Move a placed block within its current public-page column.
     *
     * @param string $direction up or down
     *
     * @return string Template filename after redirect
     */
    private function movePlacedBlock($direction)
    {
        if (!$this->objUser->isAdmin()) {
            return 'notadmin_tpl.php';
        }
        $id = (string) $this->getParam('id', '');
        if ($direction === 'up') {
            $this->objPLBlocks->moveRecUp($id);
        } else {
            $this->objPLBlocks->moveRecDown($id);
        }
        return $this->nextAction('admin');
    }

    /**
     * Remove a placed block from the public-page layout.
     *
     * @return string Template filename after redirect
     */
    private function deletePlacedBlock()
    {
        if (!$this->objUser->isAdmin()) {
            return 'notadmin_tpl.php';
        }
        $this->objPLBlocks->delete(
            'id',
            (string) $this->getParam('id', '')
        );
        return $this->nextAction('admin', array('change' => '2'));
    }

    /**
     * Return registered blocks for one editor width.
     *
     * Curation is deliberately dormant. When enabled later, registrations
     * such as `BLOCK: example|prelogin` become the public catalogue.
     *
     * @param string $width normal or wide
     *
     * @return array Normalised registered block rows
     */
    private function getBlockCatalogue($width)
    {
        $registry = $this->getObject('dbmoduleblocks', 'modulecatalogue');
        $type = $this->usesCuratedCatalogue() ? 'prelogin' : NULL;
        $catalogue = array();

        foreach ($registry->getBlocks($width, $type) as $row) {
            if ($row['moduleid'] === 'contentblocks') {
                continue;
            }
            $title = $this->objBlocks->getBlockDisplayTitle(
                $row['blockname'],
                $row['moduleid']
            );
            if ($title === FALSE) {
                continue;
            }
            $row['displaytitle'] = $title;
            $row['descriptor'] = 'block|' . $row['blockname']
                . '|' . $row['moduleid'];
            $catalogue[] = $row;
        }

        if ($this->cbExists && !$this->usesCuratedCatalogue()) {
            $contentType = $width === 'wide'
                ? 'content_widetext'
                : 'content_text';
            $contentBlocks = $this->objBlocksContent->getBlocksArr($contentType);
            if (is_array($contentBlocks)) {
                foreach ($contentBlocks as $contentBlock) {
                    $catalogue[] = array(
                        'moduleid' => 'contentblocks',
                        'blockname' => $contentBlock['id'],
                        'blockwidth' => $width,
                        'blocktype' => 'prelogin',
                        'displaytitle' => $contentBlock['title'],
                        'descriptor' => 'block|' . $contentBlock['id']
                            . '|contentblocks',
                    );
                }
            }
        }

        usort($catalogue, function ($left, $right) {
            return strcasecmp(
                (string) $left['displaytitle'],
                (string) $right['displaytitle']
            );
        });
        return $catalogue;
    }

    /**
     * Find and validate a selected block for a requested column.
     *
     * @param string $descriptor Registered block descriptor
     * @param string $side       left, middle or right
     *
     * @return array|false Catalogue row or FALSE
     */
    private function findCatalogueBlock($descriptor, $side)
    {
        if (!in_array($side, array('left', 'middle', 'right'), TRUE)) {
            return FALSE;
        }
        $width = $side === 'middle' ? 'wide' : 'normal';
        foreach ($this->getBlockCatalogue($width) as $block) {
            if (hash_equals((string) $block['descriptor'], $descriptor)) {
                return $block;
            }
        }
        return FALSE;
    }

    /**
     * Determine whether the future public-only registry filter is active.
     *
     * @return boolean TRUE only when explicitly enabled in system settings
     */
    private function usesCuratedCatalogue()
    {
        $value = $this->objSysconfig->getValue(
            'CURATE_PUBLIC_BLOCKS',
            'prelogin'
        );
        return in_array(
            strtolower((string) $value),
            array('1', 'true', 'yes'),
            TRUE
        );
    }

    /**
     * Require login for every public-page layout mutation.
     *
     * @param string|null $action Requested controller action
     *
     * @return boolean TRUE for protected editor actions
     */
    public function requiresLogin($action = null)
    {
        return in_array(
            (string) $this->getParam('action'),
            array('admin', 'addregisteredblock', 'moveup', 'movedown', 'delete'),
            TRUE
        );
    }
}

?>
