<?php
namespace Page\Acceptance;

class Login
{
    // include url of current page
    public static $URL = '/login.php';

    public static $usernameField = '#login';
    public static $passwordField = '#passwd';
    public static $loginButton = "form[name=login_form] input[type=submit]";

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }

    public function login($name = 'admin', $password = 'suklaa')
    {
        $I = $this->acceptanceTester;

        $I->amOnPage(self::$URL);
        $I->fillField(static::$usernameField, $name);
        $I->fillField(static::$passwordField, $password);
        $I->click(static::$loginButton);
    }
}
