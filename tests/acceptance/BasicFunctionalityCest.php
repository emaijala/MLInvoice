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
        $I->see('Value missing: IBAN');
        $I->fillField('IBAN', 'TESTIBAN');
        $I->click('Save');
        $I->amOnPage('/index.php?func=settings&list=base&form=base&id=1');
    }
}
