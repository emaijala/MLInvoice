<?php

use Page\Acceptance\Login;

class BasicFunctionalityCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function badLogin(AcceptanceTester $I, Login $loginPage)
    {
        $I->amOnPage('/');
        $I->dontSee('Database upgrade failed');
        $loginPage->login('admin', 'wrong');
        $I->see('Invalid user name or password.');
    }

    public function login(AcceptanceTester $I, Login $loginPage)
    {
        $I->dontSee('Database upgrade failed');
        $loginPage->login();
        $I->see('Start Page');
        $I->waitForJS("return $.active == 0;", 5);
    }

    public function createCompany(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login();
        $I->click('Settings');
        $I->click('Companies');
        $I->waitForText('No records to display');
        $I->click('New Company');
        $I->fillField('Company Name', 'Invoicer');
        $I->fillField('VAT ID', '12345');
        $I->fillField('Bank', 'Test Bank');
        $I->fillField('SWIFT/BIC', 'TESTBIC');
        $I->click('Save');
        $I->waitForText('Value missing: IBAN');
        $I->fillField('IBAN', 'TESTIBAN');
        $I->click('Save');
        $I->seeInCurrentUrl('&id=1');
    }

    public function createClient(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login();
        $I->click('Clients');
        $I->waitForText('No records to display');
        $I->click('New Client');
        $I->click('Save');
        $I->waitForText('Value missing: Client Name');
        $I->fillField('Client Name', 'Invoice Client');
        $I->fillField('VAT ID', '54321');
        $I->fillField('Email', 'client@localhost');
        $I->click('Save');
        $I->seeInCurrentUrl('&id=1');
    }

    public function createProduct(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login();
        $I->click('Settings');
        $I->click('Products');
        $I->waitForText('No records to display');
        $I->click('New Product');
        $I->fillField('Product Code', 'P1');
        $I->fillField('Product Name', 'Test Product');
        $I->fillField('Product Description', 'Super product');
        $I->fillField('Unit Price', '10,50');
        $I->selectOption('#type_id', 'pcs');
        $I->click('Save');
        $I->seeInCurrentUrl('&id=1');
    }

    #[Depends('createCompany', 'createClient', 'createProduct')]
    public function createInvoices(AcceptanceTester $I, Login $loginPage)
    {
        $I->amOnPage('/');
        $loginPage->login();
        $I->click('Invoices and Offers');
        $I->click('New Invoice');
        $I->select2Select('company_id', 1);
        $I->click('Save');
        $I->seeInCurrentUrl('&id=1');

        // Add row
        $I->select2Select('iform_product_id', 1);
        $I->waitForFieldContents('#iform_description', 'Super product');
        $I->waitForFieldContents('#iform_price', '10.50');
        $I->fillField('#iform_pcs', '2');
        $I->click('.row-add-button');
        $I->waitForText('26.04', 2, '#itable');

        // Copy
        $I->click('Copy');
        $I->seeInCurrentUrl('&id=2');
        $I->see('Invoicer 12345', '#select2-base_id-container');
        $I->see('Invoice Client 54321', '#select2-company_id-container');

        // Refund
        $I->click('Refund Invoice');
        $I->seeInCurrentUrl('&id=3');
        $I->waitForText('-26.04', 2, '#itable');
    }
}
