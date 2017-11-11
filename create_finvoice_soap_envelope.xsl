<?xml version="1.0" encoding="UTF-8"?>

<!--
 This file is licensed under the MIT license.

 Copyright 2015-2017 Ere Maijala

 Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="stylesheet"/>
  <xsl:output method="xml" version="1.0" encoding="ISO-8859-15" indent="yes" omit-xml-declaration="yes"/>
  <xsl:template match="/invoicedata">

<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:eb="http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd">
  <SOAP-ENV:Header>
    <eb:MessageHeader xmlns:eb="http://www.oasis-open.org/committees/ebxml-msg/schema/msg-header-2_0.xsd" eb:version="2.0" SOAP-ENV:mustUnderstand="1">
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
      <eb:ConversationId><xsl:value-of select="sender/org_unit_number"/>-<xsl:value-of select="invoice/invoice_no"/></eb:ConversationId>
      <eb:Service>Routing</eb:Service>
      <eb:Action>ProcessInvoice</eb:Action>
      <eb:MessageData>
        <eb:MessageId><xsl:value-of select="invoice/invoice_no"/></eb:MessageId>
        <eb:Timestamp><xsl:value-of select="settings/current_timestamp_utc"/></eb:Timestamp>
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
