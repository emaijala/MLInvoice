<?php

require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinter extends InvoicePrinterBase
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
  
    $this->emailFrom = getRequest('email_from', isset($senderData['invoice_email_from']) ? $senderData['invoice_email_from'] : (isset($senderData['email']) ? $senderData['email'] : ''));
    $this->emailTo = getRequest('email_to', isset($recipientData['email']) ? $recipientData['email'] : '');
    $this->emailCC = getRequest('email_cc', '');
    $this->emailBCC = getRequest('email_bcc', isset($senderData['invoice_email_bcc']) ? $senderData['invoice_email_bcc'] : '');
    $this->emailSubject = $this->replacePlaceholders(getRequest('email_subject', isset($senderData['invoice_email_subject']) ? $senderData['invoice_email_subject'] : ''));
    $this->emailBody = $this->replacePlaceholders(getRequest('email_body', isset($senderData['invoice_email_body']) ? $senderData['invoice_email_body'] : ''));
    
    $send = getRequest('email_send', '');
    if (!$send || !$this->emailFrom || !$this->emailTo || !$this->emailSubject || !$this->emailBody)
    {
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
<div class="ui-widget">

<div id="email_form_container" class="form_container ui-widget-content">
  <h1><?php echo $GLOBALS['locSendEmail']?></h1>
<?php if ($submitted) echo '<div class="ui-state-error-text">' . $GLOBALS['locEmailFillRequiredFields'] . "<br><br></div>\n";?>
  <form method="POST" id="email_form">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars(getRequest('id', ''))?>">
    <input type="hidden" name="template" value="<?php echo htmlspecialchars(getRequest('template', ''))?>">
    <input type="hidden" name="email_send" value="1">
    <input type="hidden" name="func" value="<?php echo htmlspecialchars(getRequest('func', ''))?>">
    <div class="medium_label"><?php echo $GLOBALS['locEmailFrom']?></div> <div class="field"><input type="text" id="email_from" name="email_from" class="medium" value="<?php echo htmlspecialchars($this->emailFrom)?>"></div>
    <div class="medium_label"><?php echo $GLOBALS['locEmailTo']?></div> <div class="field"><input type="text" id="email_to" name="email_to" class="medium" value="<?php echo htmlspecialchars($this->emailTo)?>"></div>
    <div class="medium_label"><?php echo $GLOBALS['locEmailCC']?></div> <div class="field"><input type="text" id="email_cc" name="email_cc" class="medium"></div>
    <div class="medium_label"><?php echo $GLOBALS['locEmailBCC']?></div> <div class="field"><input type="text" id="email_bcc" name="email_bcc" class="medium" value="<?php echo htmlspecialchars($this->emailBCC)?>"></div>
    <div class="medium_label"><?php echo $GLOBALS['locEmailSubject']?></div> <div class="field"><input type="text" id="email_subject" name="email_subject" class="medium" value="<?php echo htmlspecialchars($this->emailSubject)?>"></div>
    <div class="medium_label"><?php echo $GLOBALS['locEmailBody']?></div> <div class="field"><textarea id="emailBody" name="email_body" class="email_body" cols="80" rows="24"><?php echo htmlspecialchars($this->emailBody)?></textarea></div>
    <div class="form_buttons" style="clear: both">
      <a class="actionlink" onclick="document.getElementById('email_form').submit(); return false;" href="#"><?php echo $GLOBALS['locSend']?></a>
      <a class="actionlink" onclick="if (window.opener) window.close(); else history.back(); return false;" href="#"><?php echo $GLOBALS['locCancel']?></a>
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
    
    $headers = array(
      'Date' => date('r'),
      'From' => $this->emailFrom,
      'Cc' => $this->emailCC,
      'Bcc' => $this->emailBCC,
      'Mime-Version' => '1.0',
      'Content-Type' => "multipart/mixed; boundary=\"${boundary}\"",
      'X-Mailer' => 'VLLasku',
    );
      

    $filename = $this->outputFileName ? $this->outputFileName : getSetting('invoice_pdf_filename');
    $data = $pdf->Output(sprintf($filename, $invoiceData['invoice_no']), 'E');
        
    $messageBody = "This is a multipart message in mime format.\r\n\r\n";
    $messageBody .= "--$boundary\r\n";
    $messageBody .= "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n";
    $messageBody .= "Content-Transfer-Encoding: 8bit\r\n";
    $messageBody .= "Content-Disposition: inline\r\n\r\n";
    $messageBody .= $this->getFlowedBody() . "\r\n";

    $messageBody .= "--$boundary\r\n";
    $messageBody .= $data;
    $messageBody .= "\r\n--$boundary--";
  
    $result = mail($this->mimeEncodeAddress($this->emailTo), $this->mimeEncodeHeaderValue($this->emailSubject), $messageBody, $this->headersToStr($headers), '-f ' . $this->extractAddress($this->emailFrom));
    
    if ($result && $invoiceData['state_id'] == 1)
    {
      // Mark invoice sent
      mysql_param_query('UPDATE {prefix}invoice SET state_id=2 WHERE id=?', array($this->invoiceId));
    }
    $_SESSION['formMessage'] = $result ? 'EmailSent' : 'EmailFailed';
    echo header('Location: ' . _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/index.php?func=' . sanitize(getRequest('func', 'open_invoices')) . "&list=invoices&form=invoice&id={$this->invoiceId}");
  }
  
  protected function getFlowedBody()
  {
    $body = cond_utf8_encode($this->emailBody);
    
    $lines = array();
    foreach (explode("\n", $body) as $paragraph)
    {
      $line = '';
      foreach (explode(' ', $paragraph) as $word)
      {
        $word = trim($word);
        if (!$word)
          continue;
        if (strlen($line) + strlen($word) > 66)
        {
          $lines[] = "$line ";
          $line = '';
        }
        if ($line)
          $line .= " $word";
        else
          $line = $word;
      }
      $lines[] = $line;
    }
    $result = '';
    foreach ($lines as $line)
    {
      $result .= chunk_split($line, 998, "\r\n");
    }
    return $result;
  }
  
  protected function headersToStr(&$headers)
  {
    $result = '';
    foreach($headers as $header => $value)
    {
      if (!$value)
        continue;
      if (in_array($header, array("From", "To", "Cc", "Bcc")))
        $result .= "$header: " . $this->mimeEncodeAddress($value) . "\r\n";
      else
        $result .= "$header: $value\r\n";
    }
    return $result;
  }

  protected function extractAddress($address)
  {
    if (preg_match("/<(.+)>/", $address, $matches) == 1)
      return $matches[1];
    return $address;
  }
  
  
  protected function mimeEncodeAddress($address)
  {
    if (preg_match("/(.+) (<.+>)/", $address, $matches) == 1)
      $address = $this->mimeEncodeHeaderValue($matches[1]) . ' ' . $matches[2];
    elseif (preg_match("/(.+)(<.+>)/", $address, $matches) == 1)
      $address = $this->mimeEncodeHeaderValue($matches[1]) . $matches[2];
    return $address;
  }
  
  protected function mimeEncodeHeaderValue($value)
  {
    return mb_encode_mimeheader(cond_utf8_encode($value), 'UTF-8', 'Q');
  }
    
  protected function getPlaceholderData($placeholders)
  {
    $values = array();
    foreach ($placeholders as $placeholder)
    {
      $placeholder = substr(substr($placeholder, 0, -1), 1);
      $pcparts = explode(':', $placeholder);
      switch ($pcparts[0])
      {
      case 'sender': $values[] = isset($this->senderData[$pcparts[1]]) ? $this->senderData[$pcparts[1]] : ''; break;
      case 'recipient': $values[] = isset($this->recipientData[$pcparts[1]]) ? $this->recipientData[$pcparts[1]] : ''; break;
      case 'invoice': 
        switch ($pcparts[1])
        {
        case 'totalsum': $values[] = miscRound2Decim($this->totalSum); break;
        case 'totalvat': $values[] = miscRound2Decim($this->totalVAT);  break;
        case 'totalsumvat': $values[] = miscRound2Decim($this->totalSumVAT); break;
        case 'ref_number': $values[] = $this->refNumber; break; // formatted reference number
        default: 
          $value = isset($this->invoiceData[$pcparts[1]]) ? $this->invoiceData[$pcparts[1]] : '';
          if (substr($pcparts[1], -5) == '_date')
            $value = dateConvIntDate2Date($value);
          $values[] = $value;
        }
        break;
      default:
        error_log("Unknown placeholder '$placeholder' in invoice email fields");
        $values[] = '';
      }
    }
    return implode('', $values);
  }
    
  protected function replacePlaceholders($string)
  {
    return preg_replace_callback('/\{\w+:\w+\}/', array($this, 'getPlaceholderData'), $string);
  }
}
