<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="stylesheet"/>
  <xsl:output method="xml" version="1.0" encoding="iso-8859-15" indent="yes"/>
  <xsl:decimal-format name="euro" decimal-separator="," grouping-separator=""/>
  <xsl:template match="/invoicedata">
  <xsl:if test="$stylesheet!=''">
  <xsl:text disable-output-escaping="yes">&lt;?xml-stylesheet type="text/xsl" href="</xsl:text><xsl:value-of select="$stylesheet"/><xsl:text disable-output-escaping="yes">"?&gt;&#10;</xsl:text>
  </xsl:if>

<Finvoice Version="1.3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="Finvoice.xsd">
    <xsl:apply-templates select="sender"/>
    <xsl:apply-templates select="recipient"/>
    <xsl:apply-templates select="invoice"/>
</Finvoice>
  </xsl:template>
  
  <xsl:template match="sender">
  <SellerPartyDetails>
    <SellerPartyIdentifier><xsl:value-of select="company_id"/></SellerPartyIdentifier>
    <SellerOrganisationName><xsl:value-of select="name"/></SellerOrganisationName>
    <SellerOrganisationTaxCode><xsl:value-of select="vat_id"/></SellerOrganisationTaxCode>
    <xsl:if test="street_address!='' and zip_code!='' and city !=''">
    <SellerPostalAddressDetails>
      <SellerStreetName><xsl:value-of select="street_address"/></SellerStreetName>
      <SellerTownName><xsl:value-of select="city"/></SellerTownName>
      <SellerPostCodeIdentifier><xsl:value-of select="zip_code"/></SellerPostCodeIdentifier>
    </SellerPostalAddressDetails>
    </xsl:if>
  </SellerPartyDetails>
  <SellerOrganisationUnitNumber><xsl:value-of select="org_unit_number"/></SellerOrganisationUnitNumber>
    <xsl:if test="contact_person!=''">
  <SellerContactPersonName><xsl:value-of select="contact_person"/></SellerContactPersonName>
    </xsl:if>
    <xsl:if test="phone!='' or email!=''">
  <SellerCommunicationDetails>
      <xsl:if test="phone!=''">
    <SellerPhoneNumberIdentifier><xsl:value-of select="phone"/></SellerPhoneNumberIdentifier>
      </xsl:if>
      <xsl:if test="email!=''">
    <SellerEmailaddressIdentifier><xsl:value-of select="email"/></SellerEmailaddressIdentifier>
      </xsl:if>
  </SellerCommunicationDetails>
    </xsl:if>
  <SellerInformationDetails>
      <xsl:if test="vat_registered!=0">
    <SellerVatRegistrationText>Alv.Rek</SellerVatRegistrationText>
      </xsl:if>
      <xsl:if test="www!=''">
    <SellerWebaddressIdentifier><xsl:value-of select="www"/></SellerWebaddressIdentifier>
      </xsl:if>
      <xsl:if test="bank_iban!=''">
    <SellerAccountDetails>
      <SellerAccountID IdentificationSchemeName="IBAN"><xsl:call-template name="string-replace-all"><xsl:with-param name="text" select="bank_iban" /><xsl:with-param name="replace" select="' '" /><xsl:with-param name="by" select="''"/></xsl:call-template></SellerAccountID>
      <SellerBic IdentificationSchemeName="BIC"><xsl:value-of select="bank_swiftbic"/></SellerBic>  
    </SellerAccountDetails>
      </xsl:if>
      <xsl:if test="bank_iban2!=''">
    <SellerAccountDetails>
      <SellerAccountID IdentificationSchemeName="IBAN"><xsl:call-template name="string-replace-all"><xsl:with-param name="text" select="bank_iban2" /><xsl:with-param name="replace" select="' '" /><xsl:with-param name="by" select="''"/></xsl:call-template></SellerAccountID>
      <SellerBic IdentificationSchemeName="BIC"><xsl:value-of select="bank_swiftbic2"/></SellerBic>  
    </SellerAccountDetails>
      </xsl:if>
      <xsl:if test="bank_iban3!=''">
    <SellerAccountDetails>
      <SellerAccountID IdentificationSchemeName="IBAN"><xsl:call-template name="string-replace-all"><xsl:with-param name="text" select="bank_iban3" /><xsl:with-param name="replace" select="' '" /><xsl:with-param name="by" select="''"/></xsl:call-template></SellerAccountID>
      <SellerBic IdentificationSchemeName="BIC"><xsl:value-of select="bank_swiftbic3"/></SellerBic>  
    </SellerAccountDetails>
      </xsl:if>
  </SellerInformationDetails>
  </xsl:template>

  <xsl:template match="recipient">
  <BuyerPartyDetails>
    <BuyerPartyIdentifier><xsl:value-of select="company_id"/></BuyerPartyIdentifier>
    <BuyerOrganisationName><xsl:value-of select="company_name"/></BuyerOrganisationName>
    <xsl:if test="vat_id!=''">
      <BuyerOrganisationTaxCode><xsl:value-of select="vat_id"/></BuyerOrganisationTaxCode>
    </xsl:if>
    <xsl:if test="street_address!='' and zip_code!='' and city !=''">
    <BuyerPostalAddressDetails>
      <BuyerStreetName><xsl:value-of select="street_address"/></BuyerStreetName>
      <BuyerTownName><xsl:value-of select="city"/></BuyerTownName>
      <BuyerPostCodeIdentifier><xsl:value-of select="zip_code"/></BuyerPostCodeIdentifier>
    </BuyerPostalAddressDetails>
    </xsl:if>
  </BuyerPartyDetails>
  <BuyerOrganisationUnitNumber><xsl:value-of select="org_unit_number"/></BuyerOrganisationUnitNumber>
    <xsl:if test="contact_person!=''">
  <BuyerContactPersonName><xsl:value-of select="contact_person"/></BuyerContactPersonName>
    </xsl:if>
    <xsl:if test="phone!='' or email!=''">
  <BuyerCommunicationDetails>
      <xsl:if test="phone!=''">
    <BuyerPhoneNumberIdentifier><xsl:value-of select="phone"/></BuyerPhoneNumberIdentifier>
      </xsl:if>
      <xsl:if test="phone!=''">
    <BuyerEmailaddressIdentifier><xsl:value-of select="email"/></BuyerEmailaddressIdentifier>
      </xsl:if>
  </BuyerCommunicationDetails>
    </xsl:if>
  </xsl:template>
  
  <xsl:template match="invoice">
  <InvoiceDetails>
    <xsl:choose>
      <xsl:when test="state_id=5 or state_id=6">
    <InvoiceTypeCode>INV08</InvoiceTypeCode>
    <InvoiceTypeText>HUOMAUTUSLASKU</InvoiceTypeText>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="totalsum &lt; 0">
    <InvoiceTypeCode>INV02</InvoiceTypeCode>
    <InvoiceTypeText>HYVITYSLASKU</InvoiceTypeText>
          </xsl:when>
          <xsl:otherwise>
    <InvoiceTypeCode>INV01</InvoiceTypeCode>
    <InvoiceTypeText>LASKU</InvoiceTypeText>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
    <OriginCode>Original</OriginCode>
    <InvoiceNumber><xsl:value-of select="invoice_no"/></InvoiceNumber>
    <InvoiceDate Format="CCYYMMDD"><xsl:value-of select="invoice_date"/></InvoiceDate>
    <xsl:if test="ourreference!=''">
    <SellerReferenceIdentifier><xsl:value-of select="ourreference"/></SellerReferenceIdentifier>
    </xsl:if>
    <xsl:if test="reference!=''">
    <OrderIdentifier><xsl:value-of select="reference"/></OrderIdentifier>
    </xsl:if>
    <InvoiceTotalVatExcludedAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(totalsum, '0,00', 'euro')"/></InvoiceTotalVatExcludedAmount>
    <InvoiceTotalVatAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(totalvat, '0,00', 'euro')"/></InvoiceTotalVatAmount>
    <InvoiceTotalVatIncludedAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(totalsumvat, '0,00', 'euro')"/></InvoiceTotalVatIncludedAmount>
    <xsl:for-each select="groupedvats/*">
    <VatSpecificationDetails>
      <VatBaseAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(totalsum, '0,00', 'euro')"/></VatBaseAmount>
      <VatRatePercent><xsl:value-of select="format-number(vat, '0,0#', 'euro')"/></VatRatePercent>
      <VatRateAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(totalvat, '0,00', 'euro')"/></VatRateAmount>
    </VatSpecificationDetails>
    </xsl:for-each>
    <xsl:if test="info!=''">
    <InvoiceFreeText><xsl:value-of select="substring(info, 1, 512)"/></InvoiceFreeText>
    </xsl:if>
    <PaymentTermsDetails>
      <PaymentTermsFreeText><xsl:value-of select="../settings/invoice_terms_of_payment"/></PaymentTermsFreeText>
      <InvoiceDueDate Format="CCYYMMDD"><xsl:value-of select="due_date"/></InvoiceDueDate>
      <PaymentOverDueFineDetails>
        <PaymentOverDueFineFreeText><xsl:value-of select="../settings/invoice_penalty_interest_desc"/></PaymentOverDueFineFreeText>
        <PaymentOverDueFinePercent><xsl:value-of select="format-number(../settings/invoice_penalty_interest, '0,0#', 'euro')"/></PaymentOverDueFinePercent>
      </PaymentOverDueFineDetails>
    </PaymentTermsDetails>
  </InvoiceDetails>
  <PaymentStatusDetails>
    <xsl:choose>
     <xsl:when test="totalsum &lt; 0">
    <PaymentStatusCode>PAID</PaymentStatusCode>
     </xsl:when>
     <xsl:otherwise>
    <PaymentStatusCode>NOTPAID</PaymentStatusCode>
     </xsl:otherwise>
    </xsl:choose>
  </PaymentStatusDetails>
  <VirtualBankBarcode><xsl:value-of select="barcode"/></VirtualBankBarcode>
  <xsl:for-each select="rows/row">
  <InvoiceRow>
    <xsl:if test="product_code!=''">
    <ArticleIdentifier><xsl:value-of select="product_code"/></ArticleIdentifier>
    </xsl:if>
    <xsl:choose>
      <xsl:when test="product_name!='' and description!=''">
    <ArticleName><xsl:value-of select="product_name"/> (<xsl:value-of select="description"/>)</ArticleName>
      </xsl:when>
      <xsl:when test="product_name!=''">
    <ArticleName><xsl:value-of select="product_name"/></ArticleName>
      </xsl:when>
      <xsl:when test="description!=''">
    <ArticleName><xsl:value-of select="description"/></ArticleName>
      </xsl:when>
      <xsl:otherwise>
    <ArticleName>--</ArticleName>
      </xsl:otherwise>
    </xsl:choose>
    <DeliveredQuantity QuantityUnitCode="{@type}"><xsl:value-of select="format-number(pcs, '0,00', 'euro')"/></DeliveredQuantity>
    <UnitPriceAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(price, '0,00###', 'euro')"/></UnitPriceAmount>
    <RowDeliveryDate Format="CCYYMMDD"><xsl:value-of select="row_date"/></RowDeliveryDate>
    <RowDiscountPercent><xsl:value-of select="format-number(discount, '0,0#', 'euro')"/></RowDiscountPercent>
    <RowVatRatePercent><xsl:value-of select="format-number(vat, '0,0#', 'euro')"/></RowVatRatePercent>
    <RowVatAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(rowvat, '0,00###', 'euro')"/></RowVatAmount>
    <RowVatExcludedAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(rowsum, '0,00###', 'euro')"/></RowVatExcludedAmount>
    <RowAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(rowsumvat, '0,00###', 'euro')"/></RowAmount>
  </InvoiceRow>
  </xsl:for-each>
  <EpiDetails>
    <EpiIdentificationDetails>
      <EpiDate Format="CCYYMMDD"><xsl:value-of select="invoice_date"/></EpiDate>
      <xsl:if test="ref_number!=''">
      <EpiReference><xsl:value-of select="ref_number"/></EpiReference>
      </xsl:if>
    </EpiIdentificationDetails>
    <EpiPartyDetails>
      <EpiBfiPartyDetails>
        <EpiBfiIdentifier IdentificationSchemeName="BIC"><xsl:value-of select="../sender/bank_swiftbic"/></EpiBfiIdentifier>
      </EpiBfiPartyDetails>
      <EpiBeneficiaryPartyDetails>
        <EpiNameAddressDetails><xsl:value-of select="../sender/name"/></EpiNameAddressDetails>
        <EpiBei><xsl:value-of select="../sender/company_id"/></EpiBei>
        <EpiAccountID IdentificationSchemeName="IBAN"><xsl:call-template name="string-replace-all"><xsl:with-param name="text" select="../sender/bank_iban" /><xsl:with-param name="replace" select="' '" /><xsl:with-param name="by" select="''"/></xsl:call-template></EpiAccountID>
      </EpiBeneficiaryPartyDetails>
    </EpiPartyDetails>
    <EpiPaymentInstructionDetails>
      <EpiPaymentInstructionId><xsl:value-of select="invoice_no"/></EpiPaymentInstructionId>
  <xsl:choose>
    <xsl:when test="substring(formatted_ref_number, 1, 2) = 'RF'">
      <EpiRemittanceInfoIdentifier IdentificationSchemeName="ISO"><xsl:value-of select="formatted_ref_number"/></EpiRemittanceInfoIdentifier>
    </xsl:when>
    <xsl:otherwise>
      <EpiRemittanceInfoIdentifier IdentificationSchemeName="SPY"><xsl:value-of select="format-number(ref_number, '00000000000000000000')"/></EpiRemittanceInfoIdentifier>
    </xsl:otherwise>
  </xsl:choose>    
      <EpiInstructedAmount AmountCurrencyIdentifier="EUR"><xsl:value-of select="format-number(totalsumvat, '0,00', 'euro')"/></EpiInstructedAmount>
      <EpiCharge ChargeOption="SHA">SHA</EpiCharge>
      <EpiDateOptionDate Format="CCYYMMDD"><xsl:value-of select="due_date"/></EpiDateOptionDate>
    </EpiPaymentInstructionDetails>
  </EpiDetails>
  </xsl:template>
  
  <xsl:template name="string-replace-all">
    <xsl:param name="text" />
    <xsl:param name="replace" />
    <xsl:param name="by" />
    <xsl:choose>
      <xsl:when test="contains($text, $replace)">
        <xsl:value-of select="substring-before($text,$replace)" />
        <xsl:value-of select="$by" />
        <xsl:call-template name="string-replace-all">
          <xsl:with-param name="text"
          select="substring-after($text,$replace)" />
          <xsl:with-param name="replace" select="$replace" />
          <xsl:with-param name="by" select="$by" />
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$text" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
</xsl:stylesheet>
