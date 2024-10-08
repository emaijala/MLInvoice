<?php
namespace Tests\Aceptance;

use Tests\Support\AcceptanceTester;
use Tests\Support\Page\Acceptance\Client;
use Tests\Support\Page\Acceptance\Company;
use Tests\Support\Page\Acceptance\Invoice;
use Tests\Support\Page\Acceptance\Login;
use Tests\Support\Page\Acceptance\Offer;
use Tests\Support\Page\Acceptance\Product;
use Tests\Support\Page\Acceptance\Search;

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

    /**
     * Product name
     *
     * @var string
     */
    protected $productName = 'Test <small> Product';

    /**
     * Product description
     *
     * @var string
     */
    protected $productDescription = 'Super <strong> product';

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
        $client->addWithTest();
    }

    public function createProduct(AcceptanceTester $I, Login $loginPage, Product $product)
    {
        $loginPage->login();
        $product->add($this->product1, $this->productName, $this->productDescription);
        $I->seeCurrentUrlMatches('/&id=\d+/');
    }

    #[Depends('createCompany', 'createProduct')]
    public function createInvoices(AcceptanceTester $I, Login $loginPage, Invoice $invoice, Client $client)
    {
        $loginPage->login();
        $clientName = 'The Client ' . time() . 's';
        $client->add($clientName);
        $id = $invoice->add($clientName);

        // Add row
        $invoice->addRow($this->product1, $this->productDescription, 2);
        $I->waitForText("$this->product1 $this->productName", 2, '.item-row');
        $I->waitForText('26.04', 2, '#itable');

        // Copy
        $I->click('Copy');
        $I->seeInCurrentUrl('&id=' . ($id + 1));
        $I->see('Invoicer 12345', '#select2-base_id-container');
        $I->see($clientName, '#select2-company_id-container');

        // Refund
        $I->click('Refund Invoice');
        $I->seeInCurrentUrl('&id=' . ($id + 2));
        $I->waitForText('-26.04', 2, '#itable');
    }

    #[Depends('createCompany', 'createClient', 'createProduct')]
    public function editInvoice(AcceptanceTester $I, Login $loginPage, Invoice $invoice, Product $product)
    {
        $loginPage->login();
        $product->add($this->product2, $this->productName, $this->productDescription);
        $product->add($this->product3, $this->productName, $this->productDescription);
        $invoice->add(1);
        $invoice->addRow($this->product1, $this->productDescription, 2);
        $I->waitForText("$this->product1 $this->productName", 2, '.item-row td:nth-child(3)');
        $I->waitForText($this->productDescription, 2, '.item-row td:nth-child(4)');
        $I->waitForText('26.04', 2, '#itable');

        // Single edit
        $invoice->editRow(null, 4);
        $I->waitForText("$this->product1 $this->productName", 2, '.item-row td:nth-child(3)');
        $I->waitForText($this->productDescription, 2, '.item-row td:nth-child(4)');
        $I->waitForText('52.08', 2, '#itable');
        $invoice->editRow($this->product2, 4);
        $I->waitForText("$this->product2 $this->productName", 2, '.item-row td:nth-child(3)');
        $invoice->editRow('', 1, 5.00);
        $I->waitForEmpty('.item-row td:nth-child(3)');
        $I->waitForText($this->productDescription, 2, '.item-row td:nth-child(4)');
        $I->waitForText('6.20', 2, '.item-row td.row-summary');

        // Multiedit
        $invoice->addRow($this->product1, $this->productDescription, 2);
        $I->waitForText("$this->product1 $this->productName", 2, '.item-row:nth-child(3) td:nth-child(3)');
        $I->click('.cb-select-all');
        $I->click('#update-selected-rows');
        $I->select2SelectWithSearch('iform_popup_product_id', $this->product3);
        $I->fillField('#iform_popup_pcs', 10);
        $I->click('.edit-multi-buttons button[data-iform-save-rows=iform_popup]');
        $I->waitForText("$this->product3 $this->productName", 2, '.item-row:nth-child(2)');
        $I->waitForText("$this->product3 $this->productName", 2, '.item-row:nth-child(3)');
    }

    public function invoiceAndOfferMenuLists(
        AcceptanceTester $I,
        Login $loginPage,
        Client $client,
        Invoice $invoice,
        Offer $offer
    ) {
        $loginPage->login();
        $clientName = 'List Client ' . time() . 's';
        $client->add($clientName);

        // Add unarchived invoices:
        $unarchivedInvoiceIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $unarchivedInvoiceIds[] = $invoice->add($clientName, "Invoice $i");
        }

        // Add archived invoices:
        $archivedInvoiceIds = [];
        for ($i = 1; $i <= 4; $i++) {
            $archivedInvoiceIds[] = $invoice->add($clientName, "Invoice $i", true);
        }

        // Add unarchived offers:
        $unarchivedOfferIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $unarchivedOfferIds[] = $offer->add($clientName, "Offer $i");
        }

        // Add archived offers:
        $archivedOfferIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $archivedOfferIds[] = $offer->add($clientName, "Offer $i", true);
        }

        $I->click('Invoices and Offers');
        $I->click('Invoices (Non-Archived)');
        $I->fillField('#list_invoice_3_filter input', $clientName);
        $I->waitForText('1 - 5 / 5 (filtered from');
        $foundIds = $I->grabMultiple('.cb-select-row', 'value');
        $I->assertEquals($unarchivedInvoiceIds, $foundIds);

        $I->click('Invoices and Offers');
        $I->click('Archived Invoices');
        $I->fillField('#archived_invoices_3_filter input', $clientName);
        $I->waitForText('1 - 4 / 4');
        $foundIds = $I->grabMultiple('.cb-select-row', 'value');
        $I->assertEquals($archivedInvoiceIds, $foundIds);
        $I->fillField('#archived_invoices_3_filter input', "$clientName Invoice_1");
        $I->waitForText('1 - 1 / 1 (filtered from');

        $I->click('Invoices and Offers');
        $I->click('Offers (Non-Archived)');
        $I->fillField('#list_offer_3_filter input', $clientName);
        $I->waitForText('1 - 3 / 3 (filtered from');
        $foundIds = $I->grabMultiple('.cb-select-row', 'value');
        $I->assertEquals($unarchivedOfferIds, $foundIds);

        $I->click('Invoices and Offers');
        $I->click('Archived Offers');
        $I->fillField('#archived_offers_3_filter input', $clientName);
        $I->waitForText('1 - 2 / 2 (filtered from');
        $foundIds = $I->grabMultiple('.cb-select-row', 'value');
        $I->assertEquals($archivedOfferIds, $foundIds);
    }

    public function searchAndNavigateInvoices(
        AcceptanceTester $I,
        Login $loginPage,
        Client $client,
        Invoice $invoice,
        Search $search
    ) {
        $loginPage->login();
        $clientName = 'Big Client ' . time() . 's';
        $client->add($clientName);

        // Create a number of invoices:
        $ids[] = $invoice->add($clientName);
        for ($i = 1; $i < 15; $i++) {
            $ids[] = $invoice->copy();
        }

        // Search by client:
        $search->searchByClient($clientName);

        // Select first:
        $I->waitForElementClickable('tr.odd td:nth-child(2)');
        $I->click('tr.odd td:nth-child(2)');

        // Check navigation buttons:
        $I->waitForElement('.nav__previous--disabled');
        $I->seeElement('.nav__next');

        for ($i = 1; $i < 15; $i++) {
            $id = $I->grabFromCurrentUrl('/&id=(\d+)/');
            $I->assertContains((int)$id, $ids);
            $I->click('.nav__next');
            $I->waitForElementChange(
                '#record_id',
                function ($element) use ($id) {
                    return $element->getAttribute('value') !== $id;
                },
                5
            );
        }
    }
}
