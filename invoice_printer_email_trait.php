<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2016 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2016 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/

require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

trait InvoicePrinterEmailTrait
{
    protected $emailFrom = '';
    protected $emailTo = '';
    protected $emailCC = '';
    protected $emailBCC = '';
    protected $emailSubject = '';
    protected $emailBody = '';

    public function printInvoice()
    {
        $senderData = $this->senderData;
        $recipientData = $this->recipientData;
        $invoiceData = $this->invoiceData;

        $defaultRecipient = isset($recipientData['email']) ? $recipientData['email'] : '';
        $recipients = [];
        $contacts = $this->getContactPersons();
        foreach ($contacts as $contact) {
            if ($contact && !empty($contact['email'])) {
                if (!empty($contact['contact_person'])) {
                    $email = str_replace(',', ' ', $contact['contact_person'])
                        . ' <' . $contact['email'] . '>';
                } else {
                    $email = $contact['email'];
                }
                $recipients[] = $email;
            }
        }
        if ($recipients) {
            $defaultRecipient = implode(', ', $recipients);
        }

        $this->emailFrom = getRequest('email_from',
            isset($senderData['invoice_email_from']) ? $senderData['invoice_email_from'] : (isset(
                $senderData['email']) ? $senderData['email'] : ''));
        $this->emailTo = getRequest('email_to', $defaultRecipient);
        $this->emailCC = getRequest('email_cc', '');
        $this->emailBCC = getRequest('email_bcc',
            isset($senderData['invoice_email_bcc']) ? $senderData['invoice_email_bcc'] : '');
        $this->emailSubject = $this->replacePlaceholders(
            getRequest('email_subject', $this->getDefaultSubject()));
        $this->emailBody = $this->replacePlaceholders(
            getRequest('email_body', $this->getDefaultBody()));

        $send = getRequest('email_send', '');
        if (!$send || !$this->emailFrom || !$this->emailTo || !$this->emailSubject ||
             !$this->emailBody
        ) {
            $this->showEmailForm(
                $send ? $GLOBALS['locEmailFillRequiredFields'] : ''
            );
            return;
        }

        parent::printInvoice();
    }

    protected function getDefaultBody()
    {
        $key = 'invoice_email_body';
        if ($this->printStyle == 'receipt') {
            $key = 'receipt_email_body';
        }
        return isset($this->senderData[$key]) ? $this->senderData[$key] : '';
    }

    protected function getDefaultSubject()
    {
        $key = 'invoice_email_subject';
        if ($this->printStyle == 'receipt') {
            $key = 'receipt_email_subject';
        }
        return isset($this->senderData[$key]) ? $this->senderData[$key] : '';
    }

    protected function showEmailForm($errorMsg = '')
    {
        $senderData = $this->senderData;
        $recipientData = $this->recipientData;

        echo htmlPageStart($GLOBALS['locSendEmail']);
        ?>
<body>
    <div class="pagewrapper ui-widget ui-widget-content">

        <div id="email_form_container" class="form_container">
            <h1><?php echo $GLOBALS['locSendEmail']?></h1>
<?php if ($errorMsg) echo '<div class="ui-state-error-text">' . $errorMsg . "<br><br></div>\n";?>
  <form method="POST" id="email_form">
                <input type="hidden" name="id"
                    value="<?php echo htmlspecialchars(getRequest('id', ''))?>"> <input
                    type="hidden" name="template"
                    value="<?php echo htmlspecialchars(getRequest('template', ''))?>">
                <input type="hidden" name="email_send" value="1"> <input
                    type="hidden" name="func"
                    value="<?php echo htmlspecialchars(getRequest('func', ''))?>">
                <div class="medium_label"><?php echo $GLOBALS['locEmailFrom']?></div>
                <div class="field">
                    <input type="text" id="email_from" name="email_from" class="medium"
                        value="<?php echo htmlspecialchars($this->emailFrom)?>">
                </div>
                <div class="medium_label"><?php echo $GLOBALS['locEmailTo']?></div>
                <div class="field">
                    <input type="text" id="email_to" name="email_to" class="medium"
                        value="<?php echo htmlspecialchars($this->emailTo)?>">
                </div>
                <div class="medium_label"><?php echo $GLOBALS['locEmailCC']?></div>
                <div class="field">
                    <input type="text" id="email_cc" name="email_cc" class="medium"
                        value="<?php echo htmlspecialchars($this->emailCC)?>">
                </div>
                <div class="medium_label"><?php echo $GLOBALS['locEmailBCC']?></div>
                <div class="field">
                    <input type="text" id="email_bcc" name="email_bcc" class="medium"
                        value="<?php echo htmlspecialchars($this->emailBCC)?>">
                </div>
                <div class="medium_label"><?php echo $GLOBALS['locEmailSubject']?></div>
                <div class="field">
                    <input type="text" id="email_subject" name="email_subject"
                        class="medium"
                        value="<?php echo htmlspecialchars($this->emailSubject)?>">
                </div>
                <div class="medium_label"><?php echo $GLOBALS['locEmailBody']?></div>
                <div class="field">
                    <textarea id="emailBody" name="email_body" class="email_body"
                        cols="80" rows="24"><?php echo htmlspecialchars($this->emailBody)?></textarea>
                </div>
                <div class="form_buttons" style="clear: both">
                    <a class="actionlink"
                        onclick="document.getElementById('email_form').submit(); return false;"
                        href="#"><?php echo $GLOBALS['locSend']?></a> <a
                        class="actionlink"
                        onclick="if (window.opener) window.close(); else history.back(); return false;"
                        href="#"><?php echo $GLOBALS['locCancel']?></a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    }

    protected function printOut()
    {
        $pdf = $this->pdf;
        $senderData = $this->senderData;
        $invoiceData = $this->invoiceData;

        mb_internal_encoding('UTF-8');

        $filename = $this->outputFileName ? $this->outputFileName : getSetting(
            'invoice_pdf_filename');
        $filename = $this->getPrintOutFileName($filename);
        $data = $pdf->Output($filename, 'S');

        $message = Swift_Message::newInstance(
            $this->emailSubject,
            'This is a multipart message in mime format.' . PHP_EOL
        );

        $message->setSubject($this->emailSubject);
        $message->setFrom($this->extractNameAndAddress($this->emailFrom));
        $message->setTo($this->extractAddresses($this->emailTo));
        $message->setCc($this->extractAddresses($this->emailCC));
        $message->setBcc($this->extractAddresses($this->emailBCC));
        $message->addPart(
            $this->getFlowedBody(), 'text/plain; charset=UTF-8; format="flowed"'
        );

        $attachment = Swift_Attachment::newInstance(
            $data, $filename, 'application/pdf'
        );
        $message->attach($attachment);

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
                $this->showEmailForm($GLOBALS['locEmailFailed']);
                return;
            }
        } catch (Exception $e) {
            $this->showEmailForm(
                $GLOBALS['locEmailFailed'] . ': ' . $e->getMessage()
            );
            return;
        }
        if (is_callable([$this, 'emailSent'])) {
            $this->emailSent();
        }
        $_SESSION['formMessage'] = 'EmailSent';
        header(
            'Location: ' . _PROTOCOL_ . $_SERVER['HTTP_HOST'] .
                 dirname($_SERVER['PHP_SELF']) . '/index.php?func=' .
                 sanitize(getRequest('func', 'open_invoices')) .
                 "&list=invoices&form=invoice&id={$this->invoiceId}"
        );
    }

    protected function getFlowedBody()
    {
        $body = cond_utf8_encode($this->emailBody);

        $lines = [];
        foreach (explode(PHP_EOL, $body) as $paragraph) {
            $line = '';
            foreach (explode(' ', $paragraph) as $word) {
                if (strlen($line) + strlen($word) > 66) {
                    $lines[] = "$line ";
                    $line = '';
                }
                if ($line)
                    $line .= " $word";
                elseif ($word)
                    $line = $word;
                else
                    $line = ' ';
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

    protected function extractAddress($address)
    {
        if (preg_match('/<(.+)>/', $address, $matches)) {
            return $matches[1];
        }
        return $address;
    }

    protected function extractName($address)
    {
        if (preg_match('/(.+)\s*<.+>/', $address, $matches)) {
            return $matches[1];
        }
        return '';
    }

    protected function extractNameAndAddress($address)
    {
        $name = $this->extractName($address);
        $address = $this->extractAddress($address);
        return $name === '' ? $address : [$address => $name];
    }

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
                if ($name) {
                    $result[$addr] = $name;
                } else {
                    $result[$idx] = $addr;
                }
            }
        }
        return $result;
    }
}
