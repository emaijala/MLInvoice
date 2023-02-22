<?php

use Page\Acceptance\Client;
use Page\Acceptance\Company;
use Page\Acceptance\Invoice;
use Page\Acceptance\Login;
use Page\Acceptance\Product;

class BasicFunctionalityCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function badLogin(AcceptanceTester $I, Login $loginPage)
    {
        $I->amOnPage('/');
        $I->dontSee('Database upgrade failed');
        $loginPage->login('admin', 'wrong', 'Invalid user name or password.');
    }

    public function login(AcceptanceTester $I, Login $loginPage)
    {
        $I->dontSee('Database upgrade failed');
        $loginPage->login();
        $I->waitForJS("return $.active == 0;", 5);
    }

    public function createCompany(AcceptanceTester $I, Login $loginPage, Company $company)
    {
        $loginPage->login();
        $company->add();
        $I->seeCurrentUrlMatches('/&id=\d+/');
    }

    public function createClient(AcceptanceTester $I, Login $loginPage, Client $client)
    {
        $loginPage->login();
        $client->add();
        $I->seeCurrentUrlMatches('/&id=\d+/');
    }

    public function createProduct(AcceptanceTester $I, Login $loginPage, Product $product)
    {
        $loginPage->login();
        $product->add();
        $I->seeCurrentUrlMatches('/&id=\d+/');
    }

    #[Depends('createCompany', 'createClient', 'createProduct')]
    public function createInvoices(AcceptanceTester $I, Login $loginPage, Invoice $invoice)
    {
        $loginPage->login();
        $invoice->add(1);
        $I->seeInCurrentUrl('&id=');
        $id = $I->grabFromCurrentUrl('/&id=(\d+)/');

        // Add row
        $invoice->addRow(1, 2);
        $I->waitForText('26.04', 2, '#itable');

        // Copy
        $I->click('Copy');
        $I->seeInCurrentUrl('&id=' . ($id + 1));
        $I->see('Invoicer 12345', '#select2-base_id-container');
        $I->see('Invoice Client 54321', '#select2-company_id-container');

        // Refund
        $I->click('Refund Invoice');
        $I->seeInCurrentUrl('&id=' . ($id + 2));
        $I->waitForText('-26.04', 2, '#itable');
    }

    public function editInvoice(AcceptanceTester $I, Login $loginPage, Invoice $invoice)
    {
        $loginPage->login();
        $invoice->add(1);
        $invoice->addRow(1, 2);
        $I->waitForText('26.04', 2, '#itable');
        $invoice->editRow(4);
        $I->waitForText('52.08', 2, '#itable');
    }
}
