<?php

use Page\Acceptance\Client;
use Page\Acceptance\Company;
use Page\Acceptance\Invoice;
use Page\Acceptance\Login;
use Page\Acceptance\Product;

class BasicFunctionalityCest
{
    /**
     * Product code 1
     *
     * @var string
     */
    protected $product1 = '';

    /**
     * Product code 2
     *
     * @var string
     */
    protected $product2 = '';

    /**
     * Product code 3
     *
     * @var string
     */
    protected $product3 = '';

    public function _before(AcceptanceTester $I)
    {
        if ($this->product1) {
            return;
        }
        $this->product1 = 'A' . date('His') . 'A';
        $this->product2 = 'B' . date('His') . 'B';
        $this->product3 = 'C' . date('His') . 'C';
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
        $product->add($this->product1);
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
        $invoice->addRow($this->product1, 2);
        $I->waitForText("$this->product1 Test Product", 2, '.item-row');
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

    #[Depends('createCompany', 'createClient', 'createProduct')]
    public function editInvoice(AcceptanceTester $I, Login $loginPage, Invoice $invoice, Product $product)
    {
        $loginPage->login();
        $product->add($this->product2);
        $product->add($this->product3);
        $invoice->add(1);
        $invoice->addRow($this->product1, 2);
        $I->waitForText("$this->product1 Test Product", 2, '.item-row');
        $I->waitForText('Super product', 2, '.item-row');
        $I->waitForText('26.04', 2, '#itable');
        // Single edit
        $invoice->editRow(null, 4);
        $I->waitForText("$this->product1 Test Product", 2, '.item-row');
        $I->waitForText('Super product', 2, '.item-row');
        $I->waitForText('52.08', 2, '#itable');
        $invoice->editRow($this->product2, 4);
        $I->waitForText("$this->product2 Test Product", 2, '.item-row');
        // Multiedit
        $invoice->addRow($this->product1, 2);
        $I->click('.cb-select-all');
        $I->click('#update-selected-rows');
        $I->select2SelectWithSearch('iform_popup_product_id', $this->product3);
        $I->fillField('#iform_popup_pcs', 10);
        $I->click('.edit-multi-buttons button[data-iform-save-rows=iform_popup]');
        $I->waitForText("$this->product3 Test Product", 2, '.item-row:nth-child(2)');
        $I->waitForText("$this->product3 Test Product", 2, '.item-row:nth-child(3)');

    }
}
