<?php
/**
 * Doctrine LoadClassMetadata Event Listener.
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
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Database;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Doctrine LoadClassMetadata Event Listener.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class LoadClassMetadataListener
{
    /**
     * Constructor
     *
     * @patam string $tablePrefix Table name prefix
     */
    public function __construct(protected string $tablePrefix)
    {
    }

    /**
     * Event listener for loadClassMetadata event
     *
     * @param LoadClassMetadataEventArgs $eventArgs Event arguments
     *
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        // Handle table name prefix
        // (see https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/cookbook/sql-table-prefixes.html)
        $classMetadata = $eventArgs->getClassMetadata();
        if (
            !$classMetadata->isInheritanceTypeSingleTable()
            || $classMetadata->getName() === $classMetadata->rootEntityName
        ) {
            $classMetadata->setPrimaryTable([
                'name' => $this->tablePrefix . $classMetadata->getTableName()
            ]);
        }
        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if ($mapping['type'] == ClassMetadata::MANY_TO_MANY && $mapping['isOwningSide']) {
                $mappedTableName = $mapping['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name']
                    = $this->tablePrefix . $mappedTableName;
            }
        }
    }
}
