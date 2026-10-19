<?php
/** Preserve legacy goals through an explicit, repeatable catalogue upgrade. */
class context_installscripts extends ChisimbaObject
{
    public function postinstall($version)
    {
        $this->getObject('dbcontext_learneroutcomes', 'context')->migrateLegacyGoals();
    }
}
