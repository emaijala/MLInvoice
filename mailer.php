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
            ->addHeader('X-Mailer', 'MLInvoice');

        $message = (new Email())
            ->setHeaders($headers)
            ->subject($subject)
            ->from($from)
            ->text($body);

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

        try {
            switch ($settings['send_method'] ?? 'mail') {
            case 'mail':
                $transport = Transport::fromDsn('native://default');
                break;
            case 'sendmail':
                $dsn = 'sendmail://default';
                if ($command = $settings['sendmail']['command'] ?? '') {
                    $dsn .= '?command=' . urlencode($command);
                }
                $transport = Transport::fromDsn($dsn);
                break;
            case 'smtp':
                $smtp = empty($settings['smtp']) ? [] : $settings['smtp'];
                $dsn = ($smtp['security'] ?? '') === 'ssl' ? 'smtps://' : 'smtp://';
                if ($username = $smtp['username'] ?? '') {
                    $dsn .= $username;
                }
                if ($password = $smtp['password'] ?? '') {
                    $dsn .= ":$password";
                }
                $dsn .= '@' . $smtp['host'] ?? '';
                if ($port = $smtp['port'] ?? '') {
                    $dsn .= ":$port";
                }

                $transport = Transport::fromDsn($dsn);
                if (!empty($smtp['stream_context_options'])
                    && is_callable([$transport, 'getStream'])
                ) {
                    $transport->getStream()->setStreamOptions(
                        $smtp['stream_context_options']
                    );
                }
                break;
            default:
                if (empty($settings['dsn'])) {
                    $this->error = 'dsn missing from mail settings; check config.php';
                    return false;
                }
                $transport = Transport::fromDsn($settings['dsn']);
            }
            $mailer = new \Symfony\Component\Mailer\Mailer($transport);
            $mailer->send($message);
        } catch (Exception $e) {
            $this->error = Translator::translate('EmailFailed') . ': '
                . $e->getMessage();
            return false;
        }
        return true;
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
