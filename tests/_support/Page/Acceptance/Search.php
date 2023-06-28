<?php
namespace Page\Acceptance;

class Search
{
    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function searchByClient(string $name): void
    {
        $I = $this->acceptanceTester;
        $I->click('Invoices and Offers');
        $I->click('Search');
        $I->selectOption('.add-search-field', 'company_id');
        $I->select2SelectWithSearch('field-1-1', $name);
        $I->click('#search');
        $I->waitForText('Results for search ');
    }
}
