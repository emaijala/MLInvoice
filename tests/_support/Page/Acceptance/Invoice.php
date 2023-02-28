<?php
namespace Page\Acceptance;

class Invoice
{
    public static $companyField = 'base_id';
    public static $clientField = 'company_id';

    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add(int $clientId): void
    {
        $I = $this->acceptanceTester;
        $I->click('Invoices and Offers');
        $I->click('New Invoice');
        $I->select2Select(static::$companyField, 1);
        $I->select2Select(static::$clientField, $clientId);
        $I->click('Save');
        $I->waitForElementNotVisible('#inewmessage');
    }

    public function addRow(string $productCode, string $productDesc, int $pcs): void
    {
        $I = $this->acceptanceTester;
        $I->select2SelectWithSearch('iform_product_id', $productCode);
        $I->waitForFieldContents('#iform_description', $productDesc);
        $I->waitForFieldContents('#iform_price', '10.50');
        $I->fillField('#iform_pcs', $pcs);
        $I->click('.row-add-button');
    }

    public function editRow(?string $productCode, int $pcs, string $price = ''): void
    {
        $I = $this->acceptanceTester;
        $I->click('.row-edit-button');
        if (null !== $productCode) {
            if ('' === $productCode) {
                $I->select2ClearSelection('iform_popup_product_id');
            } else {
                $I->select2SelectWithSearch('iform_popup_product_id', $productCode);
            }
        }
        $I->fillField('#iform_popup_pcs', $pcs);
        if ($price) {
            $I->fillField('#iform_popup_price', $price);
        }
        $I->click('.edit-single-buttons button[data-iform-save-row=iform_popup]');
    }
}
