<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

function initInvoicePDFLocalizations($language)
{
  if (isset($language) && $language == 'en')
  {
    $GLOBALS['locPDFCompanyVATID'] = 'VAT Number';
    $GLOBALS['locPDFPhone'] = 'Tel.';
    $GLOBALS['locPDFInvoiceHeader'] = 'INVOICE';
    $GLOBALS['locPDFDispatchNoteHeader'] = 'DISPATCH NOTE';
    $GLOBALS['locPDFReceiptHeader'] = 'RECEIPT';
    $GLOBALS['locPDFFirstReminderHeader'] = 'REMINDER';
    $GLOBALS['locPDFSecondReminderHeader'] = 'REMINDER';
    $GLOBALS['locPDFCustomerNumber'] = 'Customer number';
    $GLOBALS['locPDFPeriodForComplaints'] = 'Period for Complaints';
    $GLOBALS['locPDFPenaltyInterest'] = 'Penalty Interest';
    $GLOBALS['locPDFTermsOfPayment'] = 'Terms of Payment';
    $GLOBALS['locPDFRowTax'] = 'VAT';
    $GLOBALS['locPDFRowVATPercent'] = 'VAT %';
    $GLOBALS['locPDFRowName'] = 'Item';
    $GLOBALS['locPDFRowDate'] = 'Date';
    $GLOBALS['locPDFRowPieces'] = 'Amount';
    $GLOBALS['locPDFRowPrice'] = 'Price';
    $GLOBALS['locPDFRowDiscount'] = 'Disc.%';
    $GLOBALS['locPDFRowTotalVATLess'] = "Total Exc. VAT";
    $GLOBALS['locPDFRowTotal'] = 'Total';
    $GLOBALS['locPDFTotalExcludingVAT'] = 'Total Excluding VAT';
    $GLOBALS['locPDFTotalVAT'] = 'Total VAT';
    $GLOBALS['locPDFTotalIncludingVAT'] = 'Total Including VAT';
    $GLOBALS['locPDFTotalPrice'] = 'Total price';
    $GLOBALS['locPDFSeeSeparateStatement'] = 'See separate invoice statement';
    $GLOBALS['locPDFInvoiceStatement'] = 'Invoice Statement';
    $GLOBALS['locPDFVATReg'] = 'VAT Reg.';
    $GLOBALS['locPDFNonVATReg'] = 'Non VAT Reg.';
    $GLOBALS['locPDFInvoiceNumber'] = 'Invoice Number';
    $GLOBALS['locPDFDispatchNoteNumber'] = 'Dispatch Note Number';
    $GLOBALS['locPDFReceiptNumber'] = 'Receipt Number';
    $GLOBALS['locPDFInvoiceDate'] = 'Invoice Date';
    $GLOBALS['locPDFDispatchNoteDate'] = 'Date';
    $GLOBALS['locPDFReceiptDate'] = 'Date';
    $GLOBALS['locPDFDueDate'] = 'Due Date';
    $GLOBALS['locPDFInvoiceRefNo'] = 'Reference Number';
    $GLOBALS['locPDFYourReference'] = 'Your Reference';
    $GLOBALS['locPDFRefundsInvoice'] = 'This invoice refunds invoice %d';
    $GLOBALS['locPDFFirstReminderNote'] = "Our records indicate that we have not yet received payment for this invoice. Please consult your records to verify that we have not made a mistake. If payment has already been sent, please disregard this note.";
    $GLOBALS['locPDFSecondReminderNote'] = "Our records indicate that we have still not received payment for this invoice. Please make sure a payment is sent promptly.";
    $GLOBALS['locPDFAdditionalInformation'] = 'Additional Info';
    $GLOBALS['locPDFClientVATID'] = 'Customer VAT Number';
    $GLOBALS['locPDFReminderFeeDesc'] = 'Reminder Fee';
    $GLOBALS['locPDFPenaltyInterestDesc'] = 'Penalty Interest';
    $GLOBALS['locPDFVirtualBarcode'] = 'Virtual Barcode';
    $GLOBALS['locPDFFormRecipientAccountNumber1'] = "Recipient's\naccount\nnumber";
    $GLOBALS['locPDFFormRecipientAccountNumber2'] = '';
    $GLOBALS['locPDFFormIBAN'] = 'IBAN';
    $GLOBALS['locPDFFormBIC'] = 'BIC';
    $GLOBALS['locPDFFormRecipient1'] = 'Recipient';
    $GLOBALS['locPDFFormRecipient2'] = '';
    $GLOBALS['locPDFFormPayerNameAndAddress1'] = "Payer's\nname and\naddress";
    $GLOBALS['locPDFFormPayernameAndAddress2'] = '';
    $GLOBALS['locPDFFormSignature'] = 'Signature';
    $GLOBALS['locPDFFormFromAccount'] = "From account\nno.";
    $GLOBALS['locPDFFormInvoiceNumber'] = 'Invoice number: %s';
    $GLOBALS['locPDFFormRefNumberMandatory1'] = 'Reference number must always be specified.';
    $GLOBALS['locPDFFormRefNumberMandatory2'] = '';
    $GLOBALS['locPDFFormClearingTerms1'] = "The payment will be cleared for the recipient in accordance with\nthe General terms for payment transmission and only on the basis\nof the account number given by the payer.";
    $GLOBALS['locPDFFormClearingTerms2'] = '';
    $GLOBALS['locPDFFormBank'] = 'BANK';
    $GLOBALS['locPDFFormReferenceNumber'] = 'Ref. No.';
    $GLOBALS['locPDFFormDueDate'] = 'Due date';
    $GLOBALS['locPDFFormCurrency'] = 'Euro';
    $GLOBALS['locPDFFormDueDateNOW'] = 'NOW';
  }
  else
  {
    $GLOBALS['locPDFCompanyVATID'] = 'Y-tunnus';
    $GLOBALS['locPDFPhone'] = 'Puh.';
    $GLOBALS['locPDFInvoiceHeader'] = 'LASKU';
    $GLOBALS['locPDFDispatchNoteHeader'] = 'LÄHETYSLUETTELO';
    $GLOBALS['locPDFReceiptHeader'] = 'KUITTI';
    $GLOBALS['locPDFFirstReminderHeader'] = 'MAKSUKEHOTUS';
    $GLOBALS['locPDFSecondReminderHeader'] = 'MAKSUKEHOTUS';
    $GLOBALS['locPDFCustomerNumber'] = 'Asiakasnumero';
    $GLOBALS['locPDFPeriodForComplaints'] = 'Huomautusaika';
    $GLOBALS['locPDFPenaltyInterest'] = 'Viivästyskorko';
    $GLOBALS['locPDFTermsOfPayment'] = 'Maksuehdot';
    $GLOBALS['locPDFRowTax'] = 'ALV';
    $GLOBALS['locPDFRowVATPercent'] = 'ALV %';
    $GLOBALS['locPDFRowName'] = 'Nimike';
    $GLOBALS['locPDFRowDate'] = 'Pvm';
    $GLOBALS['locPDFRowPieces'] = 'Määrä';
    $GLOBALS['locPDFRowPrice'] = 'Hinta';
    $GLOBALS['locPDFRowDiscount'] = 'Ale %';
    $GLOBALS['locPDFRowTotalVATLess'] = 'Veroton yhteensä';
    $GLOBALS['locPDFRowTotal'] = 'Yhteensä';
    $GLOBALS['locPDFTotalExcludingVAT'] = 'Arvonlisäveroton hinta yhteensä';
    $GLOBALS['locPDFTotalVAT'] = 'Arvonlisävero yhteensä';
    $GLOBALS['locPDFTotalIncludingVAT'] = 'Arvonlisäverollinen hinta yhteensä';
    $GLOBALS['locPDFTotalPrice'] = 'Hinta yhteensä';
    $GLOBALS['locPDFSeeSeparateStatement'] = 'ks. erillinen laskuerittely';
    $GLOBALS['locPDFInvoiceStatement'] = 'Laskuerittely';
    $GLOBALS['locPDFVATReg'] = 'ALV-rek.';
    $GLOBALS['locPDFNonVATReg'] = 'ei ALV-rek.';
    $GLOBALS['locPDFInvoiceNumber'] = 'Laskun numero';
    $GLOBALS['locPDFDispatchNoteNumber'] = 'Lähetysluettelon numero';
    $GLOBALS['locPDFReceiptNumber'] = 'Kuitin numero';
    $GLOBALS['locPDFInvoiceDate'] = 'Laskun päivämäärä';
    $GLOBALS['locPDFDispatchNoteDate'] = 'Päivämäärä';
    $GLOBALS['locPDFReceiptDate'] = 'Päivämäärä';
    $GLOBALS['locPDFDueDate'] = 'Eräpäivä';
    $GLOBALS['locPDFInvoiceRefNo'] = 'Viitenumero';
    $GLOBALS['locPDFYourReference'] = 'Viitteenne';
    $GLOBALS['locPDFRefundsInvoice'] = 'Tämä lasku hyvittää laskun %d';
    $GLOBALS['locPDFFirstReminderNote'] = "Kirjanpitomme mukaan laskunne on vielä maksamatta. Pyydämme teitä maksamaan laskun pikaisesti samaa viitenumeroa käyttäen. Jos lasku on jo maksettu, on tämä kehotus aiheeton.";
    $GLOBALS['locPDFSecondReminderNote'] = "Kirjanpitomme mukaan laskunne on edelleen maksamatta. Olkaa hyvä ja maksakaa lasku välittömästi samaa viitenumeroa käyttäen.";
    $GLOBALS['locPDFAdditionalInformation'] = 'Lisätiedot';
    $GLOBALS['locPDFClientVATID'] = 'Asiakkaan Y-tunnus';
    $GLOBALS['locPDFReminderFeeDesc'] = 'Maksukehotus';
    $GLOBALS['locPDFPenaltyInterestDesc'] = 'Viivästyskorko';
    $GLOBALS['locPDFVirtualBarcode'] = 'Virtuaaliviivakoodi';
    $GLOBALS['locPDFFormRecipientAccountNumber1'] = "Saajan\ntilinumero";
    $GLOBALS['locPDFFormRecipientAccountNumber2'] = "Mottagarens\nkontonummer";
    $GLOBALS['locPDFFormIBAN'] = 'IBAN';
    $GLOBALS['locPDFFormBIC'] = 'BIC';
    $GLOBALS['locPDFFormRecipient1'] = 'Saaja';
    $GLOBALS['locPDFFormRecipient2'] = 'Mottagare';
    $GLOBALS['locPDFFormPayerNameAndAddress1'] = "Maksajan\nnimi ja\nosoite";
    $GLOBALS['locPDFFormPayernameAndAddress2'] = "Betalarens\nnamn och\naddress";
    $GLOBALS['locPDFFormSignature'] = "Allekirjoitus\nUnderskrift";
    $GLOBALS['locPDFFormFromAccount'] = "Tililtä\nFrån konto nr";
    $GLOBALS['locPDFFormInvoiceNumber'] = 'Laskun numero: %s';
    $GLOBALS['locPDFFormRefNumberMandatory1'] = 'Viitenumero on aina mainittava maksettaessa.';
    $GLOBALS['locPDFFormRefNumberMandatory2'] = 'Referensnumret bör alltid anges vid betalning.';
    $GLOBALS['locPDFFormClearingTerms1'] = "Maksu välitetään saajalle maksujenvälityksen ehtojen mukaisesti ja vain\nmaksajan ilmoittaman tilinumeron perusteella";
    $GLOBALS['locPDFFormClearingTerms2'] = "Betalningen förmedlas till mottagaren enligt villkoren för betalnings-\nförmedling och endast till det kontonummer som betalaren angivit";
    $GLOBALS['locPDFFormBank'] = 'PANKKI BANKEN';
    $GLOBALS['locPDFFormReferenceNumber'] = "Viitenro\nRef. nr.";
    $GLOBALS['locPDFFormDueDate'] = "Eräpäivä\nFörfallodag";
    $GLOBALS['locPDFFormCurrency'] = 'Euro';
    $GLOBALS['locPDFFormDueDateNOW'] = 'HETI';
  }
  
  foreach ($GLOBALS as $key => &$tr)
  {
    if (substr($key, 0, 3) == 'locPDF' && is_string($tr))
    {
      if (_CHARSET_ != 'UTF-8')
        $tr = utf8_decode($tr);
    }
  }
}