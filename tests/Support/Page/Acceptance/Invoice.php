<?php
namespace Tests\Support\Page\Acceptance;

class Invoice
{
    public static $addLink = 'New Invoice';
    public static $companyField = 'base_id';
    public static $clientField = 'company_id';

    /**
     * @var \Tests\Support\AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\Tests\Support\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add($client, bool $archived = false): int
    {
        $I = $this->acceptanceTester;
        $I->click('Invoices and Offers');
        $I->click(static::$addLink);
        $I->select2Select(static::$companyField, 1);
        if (is_int($client)) {
            $I->select2Select(static::$clientField, $client);
        } else {
            $I->select2SelectWithSearch(static::$clientField, $client);
        }
        if ($archived) {
            $I->checkOption('#archived');
        }
        $I->click('Save');
        $I->waitForElementNotVisible('#inewmessage');
        $I->seeInCurrentUrl('&id=');
        return $I->grabFromCurrentUrl('/&id=(\d+)/');
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

    public function copy(): int
    {
        $I = $this->acceptanceTester;
        $currentId = $I->grabFromCurrentUrl('/&id=(\d+)/');
        $I->click('Copy');
        $I->waitForFieldContents('#record_id', $currentId + 1);
        $newId = $I->grabFromCurrentUrl('/&id=(\d+)/');
        $I->assertEquals($newId, $currentId + 1);
        return $newId;
    }
}
