<?php
/**
 * SendGrid v3 Mail Send transport.
 *
 * @author  Derek Keats
 * @package communications
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
require_once dirname(__FILE__) . '/communicationtransportinterface.php';

class sendgridtransport extends ChisimbaObject implements CommunicationTransportInterface
{
    public $objConfig;

    public function init()
    {
        $this->objConfig = $this->getObject('dbsysconfig', 'sysconfig');
    }

    public function deliver(array $message)
    {
        $apiKey = trim((string) $this->objConfig->getValue('COMMUNICATION_SENDGRID_API_KEY', 'communications'));
        if (!is_string($apiKey) || trim($apiKey) === '') {
            return $this->failure('sendgrid_secret_unavailable');
        }
        if (!function_exists('curl_init')) {
            return $this->failure('curl_unavailable');
        }

        $personalization = array('to' => array(array('email' => $message['recipient'])));
        if (!empty($message['recipient_name'])) {
            $personalization['to'][0]['name'] = $message['recipient_name'];
        }
        $from = array('email' => $message['sender']);
        if (!empty($message['sender_name'])) {
            $from['name'] = $message['sender_name'];
        }
        $content = array(array('type' => 'text/plain', 'value' => $message['body_text']));
        if (!empty($message['body_html'])) {
            $content[] = array('type' => 'text/html', 'value' => $message['body_html']);
        }
        $payload = json_encode(array(
            'personalizations' => array($personalization),
            'from' => $from,
            'subject' => $message['subject'],
            'content' => $content,
        ));
        if ($payload === false) {
            return $this->failure('json_encoding_failed');
        }

        $handle = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($handle, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . trim($apiKey),
                'Content-Type: application/json',
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ));
        $response = curl_exec($handle);
        $curlError = curl_error($handle);
        $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        curl_close($handle);

        if ($response === false) {
            return $this->failure('sendgrid_transport_error', $curlError, $code);
        }
        $headers = substr($response, 0, $headerSize);
        $body = trim(substr($response, $headerSize));
        if ($code < 200 || $code >= 300) {
            return $this->failure('sendgrid_rejected', $body, $code);
        }
        $reference = null;
        if (preg_match('/^x-message-id:\s*(.+)$/mi', $headers, $match)) {
            $reference = trim($match[1]);
        }
        return array('ok' => true, 'code' => $code, 'providerReference' => $reference);
    }

    private function failure($error, $detail = null, $code = 0)
    {
        return array('ok' => false, 'code' => (int) $code, 'error' => $error,
            'detail' => $detail === null ? $error : (string) $detail);
    }
}
?>
