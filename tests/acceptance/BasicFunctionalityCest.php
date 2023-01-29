<?php

class BasicFunctionalityCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function login(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->dontSee('Database upgrade failed. Further details in PHP error log. The system may not function properly until the upgrade has been completed.');
        $I->fillField('login', 'admin');
        $I->fillField('passwd', 'wrong');
        $I->click('Login');
        $I->see('Invalid user name or password.');
        $I->fillField('login', 'admin');
        $I->fillField('passwd', 'suklaa');
        $I->click('Login');
        $I->see('Start Page');
    }

    public function createCompany(AcceptanceTester $I)
    {
        $I->click('Settings');
        $I->see('Companies', '#navbar-dropdown-settings');
        $I->click('Companies');
        $I->see('No records to display');
        $I->click('New Company');
        $I->fillField('Company Name', 'Invoicer');
        $I->fillField('VAT ID', '12345');
        $I->fillField('Test Bank');
        $I->fillField('IBAN', 'TESTIBAN');
        $I->fillField('SWIFT/BIC', 'TESTBIC');
        $I->amOnPage('/index.php?func=settings&list=base&form=base&id=1');
    }
}
