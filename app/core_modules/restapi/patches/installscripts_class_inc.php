<?php
/** Apply API schema invariants through normal catalogue installation/update. */
class restapi_installscripts extends ChisimbaObject
{
    public function postinstall($version)
    {
        $this->getObject('restschema', 'restapi')->upgrade();
    }
}
