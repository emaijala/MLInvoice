<?php
/**
 * Doctrine EntityManager Factory.
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

namespace MLInvoice\Database;

use DI\Container;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMSetup;
use Exception;
use Psr\Container\ContainerInterface;

/**
 * Doctrine EntityManager Factory.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class EntityManagerFactory
{
    /**
     * Create EntityManager.
     *
     * @param ContainerInterface $c Container
     *
     * @return EntityManager
     */
    public static function create(ContainerInterface $c): EntityManager
    {
        $config = $c->get('config');
        $dsn = $config['Database']['dsn'] ?? null;
        if (!$dsn) {
            throw new Exception('Database connection configuration (Database/dsn) missing from config file');
        }
        $dsnParser = new DsnParser(['mysql' => 'mysqli', 'postgres' => 'pdo_pgsql']);
        $connectionConfig = $dsnParser->parse($dsn);
        if (!isset($connectionConfig['charset'])) {
            $connectionConfig['charset'] = 'utf8mb4';
        }
        $connection = DriverManager::getConnection($connectionConfig);

        $paths = [__DIR__ . '/Entity'];
        $ormConfig = ORMSetup::createAttributeMetadataConfig($paths, 'development' === MLINVOICE_ENV);
        $ormConfig->setProxyDir(MLINVOICE_CACHE_DIR . '/proxies');
        $ormConfig->setProxyNamespace('doctrine');
        $entityManager = new EntityManager($connection, $ormConfig);

        // Add LoadClassMetadataListener:
        $entityManager->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new LoadClassMetadataListener($c->get('dbPrefix'))
        );

        return $entityManager;
    }
}
