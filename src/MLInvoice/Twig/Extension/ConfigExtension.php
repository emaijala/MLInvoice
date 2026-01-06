<?php
/**
 * Twig Config Extension.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2026.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Twig\Extension;

use DI\Attribute\Inject;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\Database\Repository\PrintTemplateRepository;
use MLInvoice\I18n\Translator;
use Odan\Session\SessionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig Config Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class ConfigExtension extends AbstractExtension
{
    /**
     * Constructor
     *
     * @param array $config MLInvoice configuration
     * @param SettingsManager $settingsManager Settings manager
     * @param SessionInterface $session Session
     * @param InvoiceStateRepository $invoiceStateRepository Invoice state database repository
     * @param PrintTemplateRepository $printTemplateRepository Print template database repository
     * @param Translator $translator Translator
     */
    public function __construct(
        #[Inject('config')] protected array $config,
        protected SettingsManager $settingsManager,
        protected SessionInterface $session,
        protected InvoiceStateRepository $invoiceStateRepository,
        protected PrintTemplateRepository $printTemplateRepository,
        protected Translator $translator,
    ) {
    }

    /**
     * Get Twig functions.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_config', $this->getConfig(...)),
            new TwigFunction('get_setting', $this->settingsManager->get(...)),
            new TwigFunction('get_js_config', $this->getJsConfig(...)),
        ];
    }

    /**
     * Get config value.
     *
     * @param string $section Section
     * @param string $key     Setting key
     *
     * @return string|array|null
     */
    protected function getConfig(string $section, string $key): string|array|null
    {
        return $this->config[$section][$key] ?? null;
    }

    /**
     * Get JS configuration.
     *
     * @return string
     */
    protected function getJsConfig(): string
    {
        $requiredTranslations = [
            'DecimalSeparator',
            'InvoiceDateNonCurrent',
            'InvoiceNumberNotDefined',
            'InvoiceRefNumberTooShort',
            'InvoicesTotal',
            'NoYTJResultsFound',
            'SearchYTJPrompt',
            'SettingDispatchNotes',
            'ThousandSeparator',
            'ForSelected',
            'Delete',
            'Modify',
            'ModifySelectedRows',
            'Modified',
            'ProductWeight',
            'FutureDateEntered',
            'RecordSaved',
            'RecordDeleted',
            'UnsavedData',
            'Close',
            'Attachments',
            'NoEntries',
            'ErrValueMissing',
            'RemoveAttachment',
            'LargeFile',
            'SendToClient',
            'Description',
            'Save',
            'UpdateStockBalance',
            'YesButton',
            'NoButton',
            'Edit',
            'Copy',
            'TotalExcludingVAT',
            'TotalVAT',
            'TotalIncludingVAT',
            'TotalToPay',
            'RowCopy',
            'RowModification',
            'PartialPayment',
            'Sort',
            'ReminderFeesAdded',
            'VATLess',
            'VATPart',
            'Info',
            'ServerError',
            'Total',
            'VisiblePage',
            'SearchSaved',
            'SearchEqual',
            'SearchNotEqual',
            'SearchLessThan',
            'SearchLessThanOrEqual',
            'SearchGreaterThan',
            'SearchGreaterThanOrEqual',
            'Selected',
            'Unselected',
            'YTJLanguageCode',
        ];

        foreach ($this->invoiceStateRepository->findAllNonDeleted() as $invoiceState) {
            $translations[] = $invoiceState->getName();
        }

        if ($this->settingsManager->get('check_updates')) {
            $translations = [
                ...$translations,
                ...[
                    'UpdateAvailable',
                    'UpdateAvailableTitle',
                    'UpdateInformation',
                    'UpdateNow'
                ]
            ];
        }

        $translations = [];
        foreach ($requiredTranslations as $translation) {
            $translated = $this->translator->translate($translation);
            if ($translated != $translation) {
                $translations[$translation] = $translated;
            }
        }

        $dispatchNotePrintStyle = 'none';
        if (
            ($template = $this->printTemplateRepository->getById(2))
            && !$template->getDeleted()
        ) {
            $dispatchNotePrintStyle = $template->getNewWindow() ? 'openwindow' : 'redirect';
        }

        $offerStates = array_map(
            fn ($s) => $s->getId(),
            $this->invoiceStateRepository->findAllOfferStates()
        );

        $keepAlive = $this->session->get('user') && $this->settingsManager->get('session_keepalive');
        $lang = $this->translator->translate('HTMLLanguageCode');
        $currencyDecimals = $this->settingsManager->get('unit_price_decimals');
        $dateFormat = $this->translator->translate('DateFormat');
        $dateFormat = str_replace(
            ['d', 'm', 'j', 'n', 'Y', 'y'],
            ['DD', 'MM', 'D', 'M', 'YYYY', 'YY'],
            $dateFormat
        );
        $dateRangePickerOptions = $this->translator->translate('DateRangePickerOptions');

        return json_encode(
            compact(
                'translations',
                'dispatchNotePrintStyle',
                'offerStates',
                'keepAlive',
                'lang',
                'currencyDecimals',
                'dateFormat',
                'dateRangePickerOptions',
            )
        );
    }
}
