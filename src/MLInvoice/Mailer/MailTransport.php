<?php
/**
 * Symfony Mailer transport for PHP's mail command
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2025.
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

namespace MLInvoice\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Symfony Mailer transport for PHP's mail command
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class MailTransport implements TransportInterface
{
    /**
     * Send a message
     *
     * @param RawMessage $message  Message
     * @param ?Envelope  $envelope Envelope
     *
     * @return ?SentMessage
     *
     * @throws TransportExceptionInterface
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $message = clone $message;
        $envelope = null !== $envelope ? clone $envelope : Envelope::create($message);

        $sentMessage = new SentMessage($message, $envelope);
        // Take any Bcc header filtered from SentMessage:
        $bcc = null;
        if ($message instanceof Message) {
            $bcc = $message->getHeaders()->get('Bcc');
        }
        $this->doSend($sentMessage, $bcc);
        return $sentMessage;
    }

    /**
     * Do the actual send
     *
     * @param SentMessage      $message Message
     * @oaram ?HeaderInterface $bcc     Any Bcc header
     *
     * @return void
     */
    protected function doSend(SentMessage $message, ?HeaderInterface $bcc): void
    {
        // Separate headers from body
        $messageStr = $message->toString();
        if (false !== ($endHeaders = strpos($messageStr, "\r\n\r\n"))) {
            $headers = substr($messageStr, 0, $endHeaders) . "\r\n"; // Keep last EOL
            $body = substr($messageStr, $endHeaders + 4);
        } else {
            $headers = $messageStr . "\r\n";
            $body = '';
        }

        // Extract subject and recipients, and remove them from headers to avoid
        // duplication:
        $to = '';
        $subject = '';
        $finalHeaders = [];
        foreach (array_filter(explode("\r\n", $headers)) as $header) {
            $parts = explode(': ', $header, 2);
            if ('To' === $parts[0]) {
                $to = $parts[1] ?? '';
            } elseif ('Subject' === $parts[0]) {
                $subject = $parts[1] ?? '';
            } else {
                $finalHeaders[] = $header;
            }
        }
        if ($bcc) {
            $finalHeaders[] = $bcc->toString();
        }

        if (!mail($to, $subject, $body, implode("\r\n", $finalHeaders))) {
            throw new TransportException(
                'PHP mail command did not accept the message for delivery'
            );
        }
    }

    /**
     * Transport as string
     */
    public function __toString(): string
    {
        return 'smtp://mail';
    }
}
