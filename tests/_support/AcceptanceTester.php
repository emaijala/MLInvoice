<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

     public function select2Select(string $fieldId, int $index): void
     {
        $this->click('[aria-labelledby="select2-' . $fieldId . '-container"]');
        $this->waitForElementVisible('#select2-' . $fieldId . '-results');
        $this->click('#select2-' . $fieldId . '-results li:nth-child(' . $index . ')');
     }

    /**
     * Wait for a field to have the given content
     *
     * @param string $field    Field selector
     * @param string $contents Field contents to wait for
     * @param int    $timeout  Timeout in seconds
     *
     * @throws \Codeception\Exception\ElementNotFound
     */
    public function waitForFieldContents(string $field, string $contents, $timeout = 5)
    {
        $this->waitForElementChange(
            $field,
            function ($element) use ($contents) {
                return $element->getAttribute('value') === $contents;
            },
            $timeout
        );
    }
}
