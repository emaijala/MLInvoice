<?php
namespace Page\Acceptance;

class Product
{
    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add(string $code, string $name, string $description): void
    {
        $I = $this->acceptanceTester;
        $I->click('Settings');
        $I->click('Products');
        $I->waitForText('New Product');
        $I->click('New Product');
        $I->fillField('Product Code', $code);
        $I->fillField('Product Name', $name);
        $I->fillField('Product Description', $description);
        $I->fillField('Unit Price', '10.50');
        $I->selectOption('#type_id', 'pcs');
        $I->click('Save');
    }
}
