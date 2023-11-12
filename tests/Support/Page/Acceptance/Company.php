<?php
namespace Tests\Support\Page\Acceptance;

class Company
{
    /**
     * @var \Tests\Support\AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\Tests\Support\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add(): void
    {
        $I = $this->acceptanceTester;
        $I->click('Settings');
        $I->click('Companies');
        $I->waitForText('New Company');
        $I->click('New Company');
        $I->fillField('Company Name', 'Invoicer');
        $I->fillField('VAT ID', '12345');
        $I->fillField('Bank', 'Test Bank');
        $I->fillField('SWIFT/BIC', 'TESTBIC');
        $I->click('Save');
        $I->waitForText('Value missing: IBAN');
        $I->fillField('IBAN', 'TESTIBAN');
        $I->click('Save');
    }
}
