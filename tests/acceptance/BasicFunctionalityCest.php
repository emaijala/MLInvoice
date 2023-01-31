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
        $I->dontSee('Database upgrade failed. Further details in PHP error log. The system may not function properly until the upgrade has been completed.');
        $loginPage->login('admin', 'wrong');
        $I->see('Invalid user name or password.');
    }

    public function login(AcceptanceTester $I, Login $loginPage)
    {
        $I->amOnPage('/');
        $I->dontSee('Database upgrade failed. Further details in PHP error log. The system may not function properly until the upgrade has been completed.');
        $loginPage->login();
        $I->see('Start Page');
        $I->waitForJS("return $.active == 0;", 5);
    }

    public function createCompany(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login();
        $I->click('Settings');
        $I->click('Companies');
        $I->waitForJS("return $.active == 0;", 5);
        $I->see('No records to display');
        $I->click('New Company');
        $I->fillField('Company Name', 'Invoicer');
        $I->fillField('VAT ID', '12345');
        $I->fillField('Bank', 'Test Bank');
        $I->fillField('SWIFT/BIC', 'TESTBIC');
        $I->click('Save');
        $I->waitForText('Value missing: IBAN');
        $I->fillField('IBAN', 'TESTIBAN');
        $I->click('Save');
        $I->amOnPage('/index.php?func=settings&list=base&form=base&id=1');
    }

    public function createClient(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login();
        $I->click('Clients');
        $I->waitForJS("return $.active == 0;", 5);
        $I->see('No records to display');
        $I->click('New Client');
        $I->click('Save');
        $I->waitForText('Value missing: Client Name');
        $I->fillField('Client Name', 'Invoice Client');
        $I->fillField('VAT ID', '54321');
        $I->fillField('Email', 'client@localhost');
        $I->click('Save');
        $I->amOnPage('/index.php?func=company&form=company&id=1');
    }

    public function createInvoice(AcceptanceTester $I, Login $loginPage)
    {
        $loginPage->login();
        $I->click('Invoices and Offers');
        $I->click('New Invoice');
        $I->click('[aria-labelledby="select2-company_id-container"]');
        $I->waitForElementVisible('#select2-company_id-results');
        $I->click('#select2-company_id-results li:first-child');
        $I->click('Save');
        $I->amOnPage('/index.php?func=invoices&form=invoice&id=1');
    }
}
