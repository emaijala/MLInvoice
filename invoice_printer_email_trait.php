<?php
/**
 * Email invoice trait
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
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
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';
require_once 'mailer.php';

/**
 * Email invoice trait
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
trait InvoicePrinterEmailTrait
{
    protected $emailFrom = '';
    protected $emailTo = '';
    protected $emailCC = '';
    protected $emailBCC = '';
    protected $emailSubject = '';
    protected $emailBody = '';

    /**
     * Main method for printing
     *
     * @return void
     */
    public function printInvoice()
    {
        if (!$this->authenticated) {
            parent::printInvoice();
            return;
        }
        $senderData = $this->senderData;
        $recipientData = $this->recipientData;
        $invoiceData = $this->invoiceData;

        $defaultId = getRequest('default_body_text');
        $defaultValue = $defaultId ? getDefaultValue($defaultId, true) : null;
        $defaultSettings = $this->parseDefaultSettings($defaultValue);

        $defaultRecipient = isset($recipientData['email'])
            ? $recipientData['email'] : '';
        $recipients = [];
        $contacts = $this->getContactPersons();
        foreach ($contacts as $contact) {
            if ($contact && !empty($contact['email'])) {
                if (!empty(trim($contact['contact_person']))) {
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

        $this->emailFrom = !empty($defaultSettings['from'])
            ? $defaultSettings['from'] : getRequest('email_from', '');
        if (!$this->emailFrom) {
            if (!empty($senderData['invoice_email_from'])) {
                $this->emailFrom = $senderData['invoice_email_from'];
            } elseif (!empty($senderData['email'])) {
                $this->emailFrom = $senderData['email'];
            }
        }
        $this->emailTo = getRequest('email_to', $defaultRecipient);
        $this->emailCC = !empty($defaultSettings['cc'])
            ? $defaultSettings['cc'] : getRequest('email_cc', '');
        $this->emailBCC = !empty($defaultSettings['bcc'])
            ? $defaultSettings['bcc'] : getRequest(
                'email_bcc',
                isset($senderData['invoice_email_bcc'])
                ? $senderData['invoice_email_bcc'] : ''
            );
        $this->emailSubject = $this->replacePlaceholders(
            !empty($defaultSettings['subject'])
                ? $defaultSettings['subject']
                : getRequest('email_subject', $this->getDefaultSubject())
        );

        $emailBody = '';
        if (!empty($defaultValue['content'])) {
            $emailBody = $defaultValue['content'];
        }

        $this->emailBody = $this->replacePlaceholders(
            $emailBody ? $emailBody
                : getRequest('email_body', $this->getDefaultBody())
        );

        $send = getRequest('email_send', '');
        if (!$send || !$this->emailFrom || !$this->emailTo || !$this->emailSubject
            || !$this->emailBody
        ) {
            $this->showEmailForm(
                $send ? Translator::translate('EmailFillRequiredFields') : ''
            );
            return;
        }

        parent::printInvoice();
    }

    /**
     * Get default message body
     *
     * @return string
     */
    protected function getDefaultBody()
    {
        $key = 'invoice_email_body';
        if ($this->printStyle == 'receipt') {
            $key = 'receipt_email_body';
        }
        return isset($this->senderData[$key]) ? $this->senderData[$key] : '';
    }

    /**
     * Get default subject
     *
     * @return string
     */
    protected function getDefaultSubject()
    {
        $key = 'invoice_email_subject';
        if ($this->printStyle == 'receipt') {
            $key = 'receipt_email_subject';
        }
        return isset($this->senderData[$key]) ? $this->senderData[$key] : '';
    }

    /**
     * Display the email form
     *
     * @param string $errorMsg Any error message
     *
     * @return void
     */
    protected function showEmailForm($errorMsg = '')
    {
        $senderData = $this->senderData;
        $recipientData = $this->recipientData;

        echo htmlPageStart(Translator::translate('SendEmail'));
        ?>
<body>
    <div class="pagewrapper ui-widget ui-widget-content">
        <?php echo htmlMainTabs('open_invoices'); ?>

        <div id="email_form_container" class="form_container">
            <h1><?php echo Translator::translate('SendEmail')?></h1>
            <?php if ($errorMsg) echo '<div class="ui-state-error-text">' . $errorMsg . "<br><br></div>\n";?>
            <form method="POST" id="email_form">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars(getRequest('id', ''))?>">
                <input type="hidden" name="template" value="<?php echo htmlspecialchars(getRequest('template', ''))?>">
                <input type="hidden" id="email_send" name="email_send" value="0">
                <input type="hidden" name="func" value="<?php echo htmlspecialchars(getRequest('func', ''))?>">

                <div class="medium_label"><?php echo Translator::translate('EmailFrom')?></div>
                <div class="field">
                    <input type="text" id="email_from" name="email_from" class="medium" value="<?php echo htmlspecialchars($this->emailFrom)?>">
                </div>
                <div class="medium_label"><?php echo Translator::translate('EmailTo')?></div>
                <div class="field">
                    <input type="text" id="email_to" name="email_to" class="medium" value="<?php echo htmlspecialchars($this->emailTo)?>">
                </div>
                <div class="medium_label"><?php echo Translator::translate('EmailCC')?></div>
                <div class="field">
                    <input type="text" id="email_cc" name="email_cc" class="medium" value="<?php echo htmlspecialchars($this->emailCC)?>">
                </div>
                <div class="medium_label"><?php echo Translator::translate('EmailBCC')?></div>
                <div class="field">
                    <input type="text" id="email_bcc" name="email_bcc" class="medium" value="<?php echo htmlspecialchars($this->emailBCC)?>">
                </div>
                <div class="medium_label"><?php echo Translator::translate('EmailSubject')?></div>
                <div class="field">
                    <input type="text" id="email_subject" name="email_subject" class="medium" value="<?php echo htmlspecialchars($this->emailSubject)?>">
                </div>
                <div class="medium_label"><?php echo Translator::translate('EmailBody')?></div>
                <div class="field">
                    <textarea id="emailBody" name="email_body" class="email_body" cols="80" rows="24"><?php echo htmlspecialchars($this->emailBody)?></textarea>
                    <span class="select-default-text" data-type="email" data-target="email_form" data-send-form-param="default_body_text"></span>
                </div>
                <div class="medium_label"><?php echo Translator::translate('EmailAttachments')?></div>
                <div class="field">
                    <?php
                    if (!isset($this->printParams['attachment'])
                        || $this->printParams['attachment']
                    ) {
                        $filename = $this->outputFileName ? $this->outputFileName
                            : getSetting('invoice_pdf_filename');
                        echo $this->getPrintOutFileName($filename);
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="form_buttons" style="clear: both">
                    <a class="actionlink" onclick="$('#email_send').val(1); $('#email_form').submit(); return false;" href="#">
                        <?php echo Translator::translate('Send')?>
                    </a>
                    <a class="actionlink" onclick="if (window.opener) { window.close(); } else { history.back(); } return false;" href="#">
                        <?php echo Translator::translate('Cancel')?>
                    </a>
                    <span id="spinner" style="display: none"><img src="images/spinner.gif" alt=""></span>
                </div>
            </form>
        </div>
    </div>
</body>
<script type="text/javascript">
$(document).ready(function() {
    $('#email_form').submit(function() {
        $('#spinner').show();
    });
});
</script>
</html>
<?php
    }

    /**
     * Print the printout
     *
     * @return void
     */
    protected function printOut()
    {
        if (!$this->authenticated) {
            parent::printOut();
            return;
        }
        $pdf = $this->pdf;
        $senderData = $this->senderData;
        $invoiceData = $this->invoiceData;

        mb_internal_encoding('UTF-8');

        $attachments = [];
        if (!isset($this->printParams['attachment'])
            || $this->printParams['attachment']
        ) {
            $filename = $this->outputFileName ? $this->outputFileName
                : getSetting('invoice_pdf_filename');
            $filename = $this->getPrintOutFileName($filename);
            $data = $pdf->Output($filename, 'S');
            $attachments[] = [
                'filename' => $filename,
                'data' => $data,
                'mimetype' => 'application/pdf'
            ];
        }

        $mailer = new Mailer();
        $result = $mailer->sendEmail(
            $this->emailFrom,
            $this->emailTo,
            $this->emailCC,
            $this->emailBCC,
            $this->emailSubject,
            $this->emailBody,
            $attachments
        );

        if ($result) {
            $this->showEmailForm($mailer->getErrorMessage());
        }

        if (is_callable([$this, 'emailSent'])) {
            $this->emailSent();
        }
        $_SESSION['formMessage'] = 'EmailSent';
        header(
            'Location: index.php?func='
            . sanitize(getRequest('func', 'open_invoices'))
            . "&list=invoices&form=invoice&id={$this->invoiceId}"
        );
    }

    /**
     * Parse default value
     *
     * @param array $defaultValue Default value
     *
     * @return array
     */
    protected function parseDefaultSettings($defaultValue)
    {
        if (empty($defaultValue['additional'])) {
            return [];
        }
        $settings = [];
        foreach (explode("\n", $defaultValue['additional']) as $line) {
            $parts = explode(':', $line, 2);
            if (isset($parts[1])) {
                $settings[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        return $settings;
    }
}
