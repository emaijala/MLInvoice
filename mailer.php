<?php
/**
 * Email handler
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'translator.php';
require_once 'settings.php';

 /**
  * Email handling
  *
  * @category MLInvoice
  * @package  MLInvoice\Base
  * @author   Ere Maijala <ere@labs.fi>
  * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
  * @link     http://labs.fi/mlinvoice.eng.php
  */
class Mailer
{
    /**
     * Any error that occurred while sending email
     *
     * @var string
     */
    protected $error = '';

    /**
     * Return any error that occurred while sending email
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error;
    }

    /**
     * Send email
     *
     * @param string $from        "From" address
     * @param array  $to          "To" addresses
     * @param array  $cc          "CC" addresses
     * @param array  $bcc         "BCC" addresses
     * @param string $subject     Subject
     * @param string $body        Message body
     * @param string $attachments Attachments
     *
     * @return bool Success
     */
    public function sendEmail($from, $to, $cc, $bcc, $subject, $body, $attachments)
    {
        mb_internal_encoding('UTF-8');

        $message = Swift_Message::newInstance(
            $subject,
            $this->getFlowedBody($body),
            'text/plain; format="flowed"'
        );

        $this->error = '';

        try {
            $message->setFrom($this->extractNameAndAddress($from));
        } catch (Swift_RfcComplianceException $e) {
            $this->error = Translator::translate(
                'InvalidEmailAddress', ['%%email%%' => $from]
            );
            return false;
        }
        try {
            $message->setTo($this->extractAddresses($to));
        } catch (Swift_RfcComplianceException $e) {
            $this->error = Translator::translate(
                'InvalidEmailAddress', ['%%email%%' => $to]
            );
            return false;
        }
        try {
            $message->setCc($this->extractAddresses($cc));
        } catch (Swift_RfcComplianceException $e) {
            $this->error = Translator::translate(
                'InvalidEmailAddress', ['%%email%%' => $cc]
            );
            return false;
        }
        try {
            $message->setBcc($this->extractAddresses($bcc));
        } catch (Swift_RfcComplianceException $e) {
            $this->error = Translator::translate(
                'InvalidEmailAddress', ['%%email%%' => $bcc]
            );
            return false;
        }

        foreach ($attachments as $current) {
            $attachment = Swift_Attachment::newInstance(
                $current['data'], $current['filename'], $current['mimetype']
            );
            $message->attach($attachment);
        }

        $headers = $message->getHeaders();
        $headers->addTextHeader('X-Mailer', 'MLInvoice');

        $settings = isset($GLOBALS['mlinvoice_mail_settings'])
            ? $GLOBALS['mlinvoice_mail_settings'] : [];

        if (!isset($settings['send_method']) || 'mail' === $settings['send_method']
        ) {
            $transport = Swift_MailTransport::newInstance();
        } elseif ('sendmail' === $settings['send_method']) {
            $command = empty($settings['sendmail']['command'])
                ? '/usr/sbin/sendmail -bs'
                : $settings['sendmail']['command'];
            $transport = Swift_SendmailTransport::newInstance($command);
        } elseif ('smtp' === $settings['send_method']) {
            $smtp = empty($settings['smtp']) ? [] : $settings['smtp'];
            $transport = Swift_SmtpTransport::newInstance(
                $smtp['host'], $smtp['port'], $smtp['security']
            );
            if (!empty($smtp['username'])) {
                $transport->setUsername($smtp['username']);
            }
            if (!empty($smtp['password'])) {
                $transport->setPassword($smtp['password']);
            }
            if (!empty($smtp['stream_context_options'])) {
                $transport->setStreamOptions($smtp['stream_context_options']);
            }
        }
        $mailer = Swift_Mailer::newInstance($transport);

        try {
            $result = $mailer->send($message);
            if (!$result) {
                $this->error = Translator::translate('EmailFailed');
                return false;
            }
        } catch (Exception $e) {
            $this->error = Translator::translate('EmailFailed') . ': '
                . $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Convert a message body to the flowed format
     *
     * @param string $body Message body
     *
     * @return string
     */
    protected function getFlowedBody($body)
    {
        $body = condUtf8Encode($body);

        $lines = [];
        foreach (explode(PHP_EOL, $body) as $paragraph) {
            $line = '';
            foreach (explode(' ', $paragraph) as $word) {
                if (strlen($line) + strlen($word) > 66) {
                    $lines[] = "$line ";
                    $line = '';
                }
                if ($line) {
                    $line .= " $word";
                } elseif ($word) {
                    $line = $word;
                } else {
                    $line = ' ';
                }
            }
            $line = rtrim($line);
            $line = preg_replace('/\s+' . PHP_EOL . '$/', PHP_EOL, $line);
            $lines[] = rtrim($line, ' ');
        }
        $result = '';
        foreach ($lines as $line) {
            $result .= chunk_split($line, 998, PHP_EOL);
        }
        return $result;
    }

    /**
     * Extract an email address from a string that may also contain a name
     *
     * @param string $address Email address
     *
     * @return string
     */
    protected function extractAddress($address)
    {
        if (preg_match('/<(.+)>/', $address, $matches)) {
            return $matches[1];
        }
        return $address;
    }

    /**
     * Extract name from an email address string
     *
     * @param string $address Email address
     *
     * @return string
     */
    protected function extractName($address)
    {
        if (preg_match('/(.+)\s*<.+>/', $address, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Extract name and address from an email address string
     *
     * @param string $address Email address
     *
     * @return array
     */
    protected function extractNameAndAddress($address)
    {
        $name = $this->extractName($address);
        $address = $this->extractAddress($address);
        return trim($name) === '' ? $address : [$address => $name];
    }

    /**
     * Extract names and addresses from an array of email addresses
     *
     * @param array $addresses Email addresses
     *
     * @return array
     */
    protected function extractAddresses($addresses)
    {
        $result = [];
        if ($addresses) {
            if (is_string($addresses)) {
                $addresses = array_map('trim', str_getcsv($addresses));
            }
            foreach ($addresses as $idx => $address) {
                $name = $this->extractName($address);
                $addr = $this->extractAddress($address);
                if (trim($name) !== '') {
                    $result[$addr] = $name;
                } else {
                    $result[$idx] = $addr;
                }
            }
        }
        return $result;
    }
}
