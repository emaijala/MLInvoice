<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="stylesheet"/>
  <xsl:output method="xml" version="1.0" encoding="iso-8859-15" indent="yes"/>
  <xsl:template match="/invoicedata">

<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:eb="http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd">
  <SOAP-ENV:Header>
    <eb:MessageHeader xmlns:eb="http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd" SOAP-ENV:mustUnderstand="1">
      <eb:From>
        <eb:PartyId><xsl:value-of select="sender/org_unit_number"/></eb:PartyId>
        <eb:Role>Sender</eb:Role>
      </eb:From>
    <xsl:if test="sender/payment_intermediator != ''">
      <eb:From>
        <eb:PartyId><xsl:value-of select="sender/payment_intermediator"/></eb:PartyId>
        <eb:Role>Intermediator</eb:Role>
      </eb:From>
    </xsl:if>
      <eb:To>
        <eb:PartyId><xsl:value-of select="recipient/org_unit_number"/></eb:PartyId>
				<eb:Role>Receiver</eb:Role>
      </eb:To>
    <xsl:if test="recipient/payment_intermediator != ''">
			<eb:To>
        <eb:PartyId><xsl:value-of select="recipient/payment_intermediator"/></eb:PartyId>
				<eb:Role>Intermediator</eb:Role>
			</eb:To>
	  </xsl:if>
			<eb:CPAId>yoursandmycpa</eb:CPAId>
			<eb:ConversationId></eb:ConversationId>
			<eb:Service>Routing</eb:Service>
			<eb:Action>ProcessInvoice</eb:Action>
			<eb:MessageData>
			  <eb:MessageId><xsl:value-of select="invoice/invoice_no"/></eb:MessageId>
			  <eb:Timestamp><xsl:value-of select="settings/current_timestamp_utc"/></eb:Timestamp>
        <eb:RefToMessageId/>
      </eb:MessageData>
    </eb:MessageHeader>
  </SOAP-ENV:Header>
  <SOAP-ENV:Body>
    <eb:Manifest eb:id="Manifest" eb:version="2.0">
      <eb:Reference eb:id="Finvoice">
        <xsl:attribute name="xlink:href"><xsl:value-of select="invoice/invoice_no"/></xsl:attribute>
        <eb:Schema eb:location="http://www.finvoice.info/finvoice.xsd" eb:version="2.0"/>
      </eb:Reference>
    </eb:Manifest>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
  
  </xsl:template>
</xsl:stylesheet>
