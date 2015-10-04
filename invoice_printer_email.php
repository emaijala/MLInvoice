<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala
 
 This program is free software. See attached LICENSE.
 
 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala
 
 Tämä ohjelma on vapaa. Lue oheinen LICENSE.
 
 *******************************************************************************/
require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterEmail extends InvoicePrinterBase
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
        
        if ($this->printStyle == 'receipt') {
            $defaultSubject = isset($senderData['receipt_email_subject']) ? $senderData['receipt_email_subject'] : '';
            $defaultBody = isset($senderData['receipt_email_body']) ? $senderData['receipt_email_body'] : '';
        } else {
            $defaultSubject = isset($senderData['invoice_email_subject']) ? $senderData['invoice_email_subject'] : '';
            $defaultBody = isset($senderData['invoice_email_body']) ? $senderData['invoice_email_body'] : '';
        }
        
        $this->emailFrom = getRequest('email_from', 
            isset($senderData['invoice_email_from']) ? $senderData['invoice_email_from'] : (isset(
                $senderData['email']) ? $senderData['email'] : ''));
        $this->emailTo = getRequest('email_to', 
            isset($recipientData['email']) ? $recipientData['email'] : '');
        $this->emailCC = getRequest('email_cc', '');
        $this->emailBCC = getRequest('email_bcc', 
            isset($senderData['invoice_email_bcc']) ? $senderData['invoice_email_bcc'] : '');
        $this->emailSubject = $this->replacePlaceholders(
            getRequest('email_subject', $defaultSubject));
        $this->emailBody = $this->replacePlaceholders(
            getRequest('email_body', $defaultBody));
        
        $send = getRequest('email_send', '');
        if (!$send || !$this->emailFrom || !$this->emailTo || !$this->emailSubject ||
             !$this->emailBody) {
            $this->showEmailForm($send);
            return;
        }
        
        parent::printInvoice();
    }

    protected function showEmailForm($submitted)
    {
        $senderData = $this->senderData;
        $recipientData = $this->recipientData;
        
        echo htmlPageStart(_PAGE_TITLE_ . ' - ' . $GLOBALS['locSendEmail']);
        ?>
<body>
	<div class="pagewrapper ui-widget ui-widget-content">

		<div id="email_form_container" class="form_container">
			<h1><?php echo $GLOBALS['locSendEmail']?></h1>
<?php if ($submitted) echo '<div class="ui-state-error-text">' . $GLOBALS['locEmailFillRequiredFields'] . "<br><br></div>\n";?>
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
        
        $boundary = '-----' . md5(uniqid(time())) . '-----';
        
        // Note: According to https://bugs.php.net/bug.php?id=15841 the PHP documentation is wrong,
        // and CRLF should not be used except on Windows. PHP_EOL should work.
        
        $headers = [
            'Date' => date('r'), 
            'From' => $this->emailFrom, 
            'Cc' => $this->emailCC, 
            'Bcc' => $this->emailBCC, 
            'Mime-Version' => '1.0', 
            'Content-Type' => "multipart/mixed; boundary=\"${boundary}\"", 
            'X-Mailer' => 'MLInvoice'
        ];
        
        $filename = $this->outputFileName ? $this->outputFileName : getSetting(
            'invoice_pdf_filename');
        $filename = $this->getPrintOutFileName($filename);
        $data = $pdf->Output($filename, 'E');
        
        $messageBody = 'This is a multipart message in mime format.' . PHP_EOL .
             PHP_EOL;
        $messageBody .= "--$boundary" . PHP_EOL;
        $messageBody .= 'Content-Type: text/plain; charset=UTF-8; format=flowed' .
             PHP_EOL;
        $messageBody .= 'Content-Transfer-Encoding: 8bit' . PHP_EOL;
        $messageBody .= 'Content-Disposition: inline' . PHP_EOL . PHP_EOL;
        $messageBody .= $this->getFlowedBody() . PHP_EOL;
        
        $messageBody .= "--$boundary" . PHP_EOL;
        $messageBody .= str_replace("\r\n", PHP_EOL, $data);
        $messageBody .= PHP_EOL . "--$boundary--";
        
        $result = mail($this->mimeEncodeAddress($this->emailTo), 
            $this->mimeEncodeHeaderValue($this->emailSubject), $messageBody, 
            $this->headersToStr($headers), 
            '-f ' . $this->extractAddress($this->emailFrom));
        
        if ($result && $invoiceData['state_id'] == 1) {
            // Mark invoice sent
            mysqli_param_query('UPDATE {prefix}invoice SET state_id=2 WHERE id=?', 
                [
                    $this->invoiceId
                ]);
        }
        if ($result) {
            $_SESSION['formMessage'] = 'EmailSent';
        } else {
            $_SESSION['formErrorMessage'] = 'EmailFailed';
        }
        echo header(
            'Location: ' . _PROTOCOL_ . $_SERVER['HTTP_HOST'] .
                 dirname($_SERVER['PHP_SELF']) . '/index.php?func=' .
                 sanitize(getRequest('func', 'open_invoices')) .
                 "&list=invoices&form=invoice&id={$this->invoiceId}");
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

    protected function headersToStr(&$headers)
    {
        $result = '';
        foreach ($headers as $header => $value) {
            if (!$value)
                continue;
            if (in_array($header, 
                [
                    'From', 
                    'To', 
                    'Cc', 
                    'Bcc'
                ]))
                $result .= "$header: " . $this->mimeEncodeAddress($value) . PHP_EOL;
            else
                $result .= "$header: $value" . PHP_EOL;
        }
        return $result;
    }

    protected function extractAddress($address)
    {
        if (preg_match('/<(.+)>/', $address, $matches) == 1)
            return $matches[1];
        return $address;
    }

    protected function mimeEncodeAddress($address)
    {
        if (preg_match('/(.+) (<.+>)/', $address, $matches) == 1)
            $address = $this->mimeEncodeHeaderValue($matches[1]) . ' ' . $matches[2];
        elseif (preg_match('/(.+)(<.+>)/', $address, $matches) == 1)
            $address = $this->mimeEncodeHeaderValue($matches[1]) . $matches[2];
        return $address;
    }

    protected function mimeEncodeHeaderValue($value)
    {
        return mb_encode_mimeheader(cond_utf8_encode($value), 'UTF-8', 'Q');
    }
}
