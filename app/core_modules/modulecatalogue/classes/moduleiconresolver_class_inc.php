<?php
/**
 * Resolves the semantic Lucide icon declared by a module.
 *
 * register.conf is the authoritative metadata source. The shared UI service
 * validates and renders the centrally owned Lucide asset.
 */
class moduleiconresolver extends ChisimbaObject
{
    private $moduleFile;
    private $iconService;

    public function init()
    {
        $this->moduleFile = $this->getObject('modulefile', 'modulecatalogue');
        $this->iconService = $this->getObject('iconservice', 'ui');
    }

    public function resolve($moduleId)
    {
        $path = $this->moduleFile->findRegisterFile((string) $moduleId);
        if ($path !== false) {
            $metadata = $this->moduleFile->readRegisterFile($path);
            if (is_array($metadata) && !empty($metadata['MODULE_ICON'])) {
                return (string) $metadata['MODULE_ICON'];
            }
        }
        return 'puzzle';
    }

    /* CHISIMBA_MODULE_ICON_CONSUMER_CLASS */
    public function render($moduleId, $label = '', $class = 'modcat-lucide-icon')
    {
        $name = $this->resolve($moduleId);
        try {
            return $this->iconService->render($name, array(
                'label' => (string) $label,
                'decorative' => $label === '',
                'class' => (string) $class
            ));
        } catch (Throwable $exception) {
            return $this->iconService->render('puzzle', array(
                'label' => (string) $label,
                'decorative' => $label === '',
                'class' => (string) $class
            ));
        }
    }
}
