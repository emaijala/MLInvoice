<?php
namespace Tests\Support\Page\Acceptance;

class User
{
    public static $nameField = 'User Name';
    public static $emailField = 'Email';
    public static $idField = 'User ID';
    public static $passwordField = 'Password';
    public static $userTypeField = 'User Type';

    /**
     * @var \Tests\Support\AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\Tests\Support\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function add(string $name, string $id, string $email, string $password): int
    {
        $I = $this->acceptanceTester;
        $I->click('System');
        $I->waitForText('Users');
        $I->click('Users');
        $I->click('New User');
        $I->click('Save');
        $I->waitForText(
            'Value missing: ' . static::$nameField . ', ' . static::$emailField . ', '
            . static::$idField . ', ' .static::$userTypeField
        );
        $I->fillField(static::$nameField, $name);
        $I->fillField(static::$idField, $id);
        $I->fillField(static::$emailField, $email);
        $I->fillField(static::$passwordField, $password);
        $I->selectOption(static::$userTypeField, '2');

        $I->click('Save');
        $I->waitForElement('.form_container .btn-secondary');
        $I->seeCurrentUrlMatches('/&id=\d+/');
        return $I->grabFromCurrentUrl('/&id=(\d+)/');
    }

    public function delete(string $id, string $userId, string $expectedError = null): void
    {
        $I = $this->acceptanceTester;
        $I->amOnPage("index.php?func=system&form=user&id=$id");
        $I->clickWithLeftButton(['xpath' => "//a[contains(., 'Delete')]"]);
        $I->click('Confirm Delete');
        if ($expectedError) {
            $I->waitForText($expectedError);
        } else {
            $I->dontSeeInCurrentUrl('form=user');
        }
    }
}
