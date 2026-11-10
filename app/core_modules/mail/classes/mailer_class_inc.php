<?php
/** Retained synchronous mail facade; new notification workflows use Communications. */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}
require_once __DIR__ . '/absendmail_class_inc.php';

/**
 * Preserve the Chisimba mail API using the shared, pinned MIME service.
 * @author Derek Keats
 * @copyright AVOIR
 * @licence GNU/GPL
 */
class mailer extends absendmail
{
    public $objConfig;
    protected $useHTMLMail;

    public function init()
    {
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    /** Transport acceptance, not inbox delivery. Do not expose provider details to pages. */
    public function send()
    {
        try {
            $htmlEnabled = filter_var($this->useHTMLMail ?? $this->objConfig->getValue('MAIL_USE_HTML_AS_DEFAULT', 'mail', 'false'), FILTER_VALIDATE_BOOLEAN);
            return $this->getObject('mimemailservice', 'mail')->send([
                'to'=>$this->to, 'cc'=>$this->cc, 'bcc'=>$this->bcc,
                'from'=>$this->from, 'fromName'=>$this->fromName,
                'subject'=>$this->subject, 'body'=>$this->body ?? '',
                'htmlbody'=>$htmlEnabled ? ($this->htmlbody ?? '') : '',
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /** The historical method never worked; reject explicitly instead of silently omitting files. */
    public function attach($file, $name = null)
    {
        throw new LogicException('Legacy mail attachments are unsupported; use an authorised file link.');
    }
}
