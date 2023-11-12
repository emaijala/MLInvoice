<?php
namespace Tests\Support\Page\Acceptance;

class Client
{
    public static $nameField = 'Client Name';
    public static $vatField = 'VAT ID';
    public static $emailField = 'Email';

    /**
     * @var \Tests\Support\AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\Tests\Support\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add(string $name = 'Invoice Client'): int
    {
        return $this->doAdd(false, $name);
    }

    public function addWithTest(string $name = 'Invoice Client'): int
    {
        return $this->doAdd(true, $name);
    }

    protected function doAdd(bool $test, string $name): int
    {
        $I = $this->acceptanceTester;
        $I->click('Clients');
        $I->waitForText('New Client');
        $I->click('New Client');
        if ($test) {
            $I->click('Save');
            $I->waitForText('Value missing: ' . static::$nameField);
        }
        $I->fillField(static::$nameField, $name);
        $I->fillField(static::$vatField, '54321');
        $I->fillField(static::$emailField, 'client@localhost');
        $I->click('Save');
        $I->seeCurrentUrlMatches('/&id=\d+/');
        return $I->grabFromCurrentUrl('/&id=(\d+)/');
    }
}
