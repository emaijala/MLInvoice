<?php
/**
 * Email handler
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2018-2022
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

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;

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
     * @param array  $attachments Attachments
     *
     * @return bool Success
     */
    public function sendEmail($from, $to, $cc, $bcc, $subject, $body, $attachments)
    {
        $this->error = '';
        mb_internal_encoding('UTF-8');

        $headers = (new Headers())
            ->addHeader('Content-Type', 'text/plain; format="flowed"')
            ->addHeader('X-Mailer', 'MLInvoice');

        $message = (new Email())
            ->setHeaders($headers)
            ->subject($subject)
            ->text($this->getFlowedBody($body))
            ->from($from);
        foreach (['to' => $to, 'cc' => $cc, 'bcc' => $bcc] as $func => $addresses) {
            if ($addresses) {
                call_user_func_array(
                    [$message, $func],
                    $this->extractAddresses($addresses)
                );
            }
        }

        foreach ($attachments as $current) {
            $message->attach(
                $current['data'],
                $current['filename'],
                $current['mimetype']
            );
        }

        $settings = $GLOBALS['mlinvoice_mail_settings'] ?? [];

        if ('mail' === ($settings['send_method'] ?? 'mail')) {
            $transport = Transport::fromDsn('native://default');
        } elseif ('sendmail' === $settings['send_method']) {
            $dsn = 'sendmail://default';
            if ($command = $settings['sendmail']['command'] ?? '') {
                $dsn .= '?command=' . urlencode($command);
            }
            $transport = Transport::fromDsn($settings['dsn']);
        } elseif ('smtp' === $settings['send_method']) {
            $smtp = empty($settings['smtp']) ? [] : $settings['smtp'];
            $dsn = ($smtp['security'] ?? '') === 'ssl' ? 'smtps://' : 'smtp://';
            if ($username = $smtp['username'] ?? '') {
                $dsn .= $username;
            }
            if ($password = $smtp['password'] ?? '') {
                $dsn .= ":$password";
            }
            $dsn .= "@host";
            if ($port = $smtp['port'] ?? '') {
                $dsn .= ":$port";
            }

            $transport = Transport::fromDsn($settings['dsn']);
            if (!empty($smtp['stream_context_options'])) {
                $transport->getStream()->setStreamOptions(
                    $smtp['stream_context_options']
                );
            }
        } else {
            $transport = Transport::fromDsn($settings['dsn']);
        }
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        try {
            $mailer->send($message);
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
     * Extract addresses to an array
     *
     * @param string|array $addresses Email addresses
     *
     * @return array
     */
    protected function extractAddresses($addresses): array
    {
        if ($addresses && is_string($addresses)) {
            $result = array_map('trim', str_getcsv($addresses));
        } else {
            $result = $addresses;
        }
        return $result;
    }
}
