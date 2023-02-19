<?php
namespace Page\Acceptance;

class Client
{
    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add(): void
    {
        $I = $this->acceptanceTester;
        $I->click('Clients');
        $I->waitForText('New Client');
        $I->click('New Client');
        $I->click('Save');
        $I->waitForText('Value missing: Client Name');
        $I->fillField('Client Name', 'Invoice Client');
        $I->fillField('VAT ID', '54321');
        $I->fillField('Email', 'client@localhost');
        $I->click('Save');
    }
}
