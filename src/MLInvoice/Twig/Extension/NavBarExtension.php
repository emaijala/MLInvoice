<?php
/**
 * Twig NavBar Extension.
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
use InvalidArgumentException;
use MLInvoice\Database\Entity\User;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig NavBar Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class NavBarExtension extends AbstractExtension
{
    /**
     * Constructor
     *
     * @param array $config MLInvoice configuration
     * @param SessionManagerInterface&SessionInterface $sessionManager Session manager
     */
    public function __construct(
        #[Inject('config')] protected array $config,
        #[Inject(SessionManagerInterface::class)] protected SessionManagerInterface&SessionInterface $sessionManager,
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
            new TwigFunction('navbar_elements', $this->getNavbarElements(...)),
        ];
    }

    /**
     * Get navbar elements for a UI state.
     *
     * @param string $route Route (login, logout, anything else)
     * @param ?User  $user  User, if any
     *
     * @return array
     */
    public function getNavbarElements(string $route, ?User $user): array
    {
        $result = match ($route) {
            'login' => $this->getLoginElements($user),
            'logout' => $this->getLogoutElements($user),
            default => $this->getMainElements($user),
        };
        return $result;
    }

    /**
     * Return navbar elements for login view.
     *
     * @return array
     */
    protected function getLoginElements(): array
    {
        $menu = [];
        $currentLocale = $this->sessionManager->get('locale');
        foreach ($this->config['Locales'] ?? [] as $locale => $name) {
            if ($locale === $currentLocale) {
                continue;
            }
            $menu[] = [
                'title' => $name,
                'route' => 'login',
                'params' => [
                    'lang' => $locale,
                ],
            ];
        }
        return $menu;
    }

    /**
     * Return navbar elements for logout view.
     *
     * @return array
     */
    protected function getLogoutElements(): array
    {
        return $this->getLoginElements();
    }

    /**
     * Return navbar elements for main views.
     *
     * @param ?User $user User, if any
     *
     * @return array
     */
    protected function getMainElements(?User $user): array
    {
        $normalMenuRights = [
            MLINVOICE_USER_ROLE_READONLY,
            MLINVOICE_USER_ROLE_USER,
            MLINVOICE_USER_ROLE_BACKUPMGR
        ];

        $menu = [
            [
                'title' => 'InvoicesAndOffers',
                'route' => 'invoices',
                'levels_allowed' => $normalMenuRights,
                'submenu' => [
                    [
                        'title' => 'StartPage',
                        'route' => 'home',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => '-',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'NonArchivedInvoices',
                        'route' => 'invoices',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'ArchivedInvoices',
                        'route' => 'invoices-archived',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'NewInvoice',
                        'route' => 'invoices-new',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR,
                        ],
                    ],
                    [
                        'title' => '-',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'RecurringInvoiceTemplates',
                        'route' => 'recurring-invoices',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'RecurringInvoiceTemplatesDueForProcessing',
                        'route' => 'recurring-invoices-due',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'NewRecurringInvoiceTemplate',
                        'route' => 'recurring-invoices-new',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR,
                        ],
                    ],
                    [
                        'title' => '-',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'NonArchivedOffers',
                        'route' => 'offers',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'ArchivedOffers',
                        'route' => 'offers-archived',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'NewOffer',
                        'route' => 'offers-new',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR,
                        ],
                    ],
                    [
                        'title' => '-',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'ImportAccountStatement',
                        'route' => 'import-statement',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR,
                        ],
                    ],
                    [
                        'title' => 'ExtSearch',
                        'route' => 'search-invoice',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_READONLY,
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR,
                        ],
                    ],
                ]
            ],
            [
                'name' => 'company',
                'title' => 'ShowClientNavi',
                'route' => 'companies',
                'levels_allowed' => [
                    MLINVOICE_USER_ROLE_USER,
                    MLINVOICE_USER_ROLE_BACKUPMGR
                ],
            ],
            [
                'name' => 'reports',
                'title' => 'ShowReportNavi',
                'levels_allowed' => $normalMenuRights,
                'submenu' => [
                    [
                        'title' => 'InvoiceReport',
                        'route' => 'reports-invoice',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'ProductReport',
                        'route' => 'reports-product',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'ProductStockReport',
                        'route' => 'reports-product-stock',
                        'levels_allowed' => $normalMenuRights,
                    ],
                    [
                        'title' => 'AccountingReport',
                        'route' => 'reports-accounting',
                        'levels_allowed' => $normalMenuRights,
                    ]
                ],
            ],
            [
                'name' => 'settings',
                'title' => 'ShowSettingsNavi',
                'levels_allowed' => [
                    MLINVOICE_USER_ROLE_USER,
                    MLINVOICE_USER_ROLE_BACKUPMGR
                ],
                'submenu' => [
                    [
                        'title' => 'GeneralSettings',
                        'route' => 'settings-general',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'Bases',
                        'route' => 'settings-bases',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR
                        ]
                    ],
                    [
                        'title' => 'Products',
                        'route' => 'settings-products',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR
                        ]
                    ],
                    [
                        'title' => 'DefaultValues',
                        'route' => 'settings-default-values',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR
                        ]
                    ],
                    [
                        'title' => 'Attachments',
                        'route' => 'settings-attachments',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR
                        ]
                    ],
                    [
                        'title' => 'StartPageAndSavedSearches',
                        'route' => 'settings-searches-invoice',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_USER,
                            MLINVOICE_USER_ROLE_BACKUPMGR
                        ]
                    ],
                ]
            ],
            [
                'name' => 'system',
                'title' => 'ShowSystemNavi',
                'levels_allowed' => [
                    MLINVOICE_USER_ROLE_BACKUPMGR,
                    MLINVOICE_USER_ROLE_ADMIN
                ],
                'submenu' => [
                    [
                        'title' => 'Users',
                        'route' => 'system-users',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'InvoiceStates',
                        'route' => 'system-invoice-states',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'InvoiceTypes',
                        'route' => 'system-invoice-types',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'RowTypes',
                        'route' => 'system-row-types',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'DeliveryTerms',
                        'route' => 'system-delivery-terms',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'DeliveryMethods',
                        'route' => 'system-delivery-methods',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'PrintTemplates',
                        'route' => 'system-print-templates',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'BackupDatabase',
                        'route' => 'system-backup',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_BACKUPMGR,
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'ImportData',
                        'route' => 'system-import',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'ExportData',
                        'route' => 'system-export',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ],
                    [
                        'title' => 'Update',
                        'route' => 'system-update',
                        'levels_allowed' => [
                            MLINVOICE_USER_ROLE_ADMIN
                        ]
                    ]
                ],
            ],
        ];
        return $this->filterByUser([$menu], $user)[0];
    }

    /**
     * Filter menu items by user's access level.
     *
     * @param array $menu Menu
     * @param ?User $user User, if any
     *
     * @return array
     */
    protected function filterByUser(array $menu, ?User $user): array
    {
        if (!($level = $user?->getSessionType()?->getAccessLevel())) {
            return [];
        }
        $result = [];
        foreach ($menu as $menuItem) {
            if (isset($menuItem['levels_allowed']) && !in_array($level, $menuItem['levels_allowed'])) {
                continue;
            }
            if (isset($menuItem['submenu'])) {
                $menuItem['submenu'] = $this->filterByUser($menuItem['submenu'], $user);
            }
            $result[] = $menuItem;
        }
        return $result;
    }
}
