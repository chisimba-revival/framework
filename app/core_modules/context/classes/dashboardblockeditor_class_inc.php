<?php
/** Administrator policy and chooser for the three shared account dashboards. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
$this->loadClass('contextblockeditor', 'context');
class dashboardblockeditor extends contextblockeditor
{
    private const ROLES = ['mylearning'=>'readonly', 'myteaching'=>'author', 'myadmin'=>'admin'];
    private ?string $dashboard = null;

    /** Call once with the owning controller's literal module, never request input. */
    public function forModule(string $module): self
    {
        if ($this->dashboard !== null || !isset(self::ROLES[$module])) {
            throw new InvalidArgumentException('Unknown or already configured dashboard');
        }
        $this->dashboard = $module;
        return $this;
    }
    public function canEdit(): bool
    {
        $user = $this->getObject('user', 'security');
        return $this->dashboard !== null && $user->isLoggedIn() && $user->lookupAdmin($user->userId());
    }
    public function purpose($code) { return 'dashboard_blocks_'.$this->dashboard; }
    protected function editingScope(array $input)
    {
        return $this->canEdit() && ($input['contextcode'] ?? null) === $this->dashboard
            ? $this->dashboard : null;
    }
    /** The same registered, available choices govern both the UI and requests. */
    public function options(bool $wide): array
    {
        if (!$this->canEdit()) return [];
        $options = [];
        $catalogue = $this->getObject('modules', 'modulecatalogue');
        $dynamic = $this->getObject('dynamicblocks', 'blocks');
        foreach ((array)($wide ? $dynamic->getWideSiteBlocks() : $dynamic->getSmallSiteBlocks()) as $row) {
            if (!$catalogue->checkIfRegistered($row['module'])) continue;
            $options[] = ['value'=>'dynamicblock|'.$row['id'].'|'.$row['module'],
                'title'=>(trim((string)($row['title'] ?? '')) ?: $row['module']).' ('.$row['module'].')'];
        }
        $registry = $this->getObject('dbmoduleblocks', 'modulecatalogue');
        foreach ((array)$registry->getBlocks($wide ? 'wide' : 'normal', 'site|user|postlogin', self::ROLES[$this->dashboard]) as $row) {
            if ($row['moduleid'] === 'contentblocks' || !$catalogue->checkIfRegistered($row['moduleid'])) continue;
            try {
                $block = $this->newObject('block_'.$row['blockname'], $row['moduleid']);
                if (method_exists($block, 'isAvailableForBlockChooser') && !$block->isAvailableForBlockChooser()) continue;
                $title = trim((string)$block->title) ?: $row['blockname'];
            } catch (Throwable $error) {
                // An unavailable optional block must neither break nor enter the chooser.
                continue;
            }
            $options[] = ['value'=>'block|'.$row['blockname'].'|'.$row['moduleid'],
                'title'=>html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8').' ('.$row['moduleid'].')'];
        }
        usort($options, static fn($a, $b) => strcasecmp($a['title'], $b['title']));
        return $options;
    }
    public function allowed($id, $side, $code): bool
    {
        return $code === $this->dashboard && in_array($side, ['left','right','middle'], true)
            && in_array($id, array_column($this->options($side === 'middle'), 'value'), true);
    }
    /** Revision is captured before the controller renders any placement rows. */
    public function settings(string $revision): string
    {
        if (!$this->canEdit()) return '';
        $language = $this->getObject('language', 'language');
        $attributes = ['module'=>$this->dashboard, 'contextcode'=>$this->dashboard,
            'csrf'=>$this->csrf()->issue($this->purpose($this->dashboard)), 'revision'=>$revision,
            'cancel'=>html_entity_decode($language->languageText('word_cancel'), ENT_QUOTES, 'UTF-8')];
        foreach (['saving','saved','failed','stale','uncertain','denied','method','csrf-error'] as $key) {
            $attributes[$key] = html_entity_decode($language->languageText('mod_context_block_'.($key === 'csrf-error' ? 'csrf' : $key), 'context'), ENT_QUOTES, 'UTF-8');
        }
        $html = '<div id="block-editor-settings"';
        foreach ($attributes as $key=>$value) $html .= ' data-'.$key.'="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
        return $html.'><p id="block-editor-status" role="status" aria-live="polite"></p></div>';
    }
}
