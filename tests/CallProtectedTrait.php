<?php
/**
 * Trait that provides access to protected methods
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2021.
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
 * @package  MLInvoice\Test
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

/**
 * Trait that provides access to protected methods
 *
 * @category MLInvoice
 * @package  MLInvoice\Test
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
trait CallProtectedTrait
{
    /**
     * Call a protected method
     *
     * @param Object $object Object
     * @param string $method Method name
     * @param array  $params Method params
     *
     * @return mixed
     */
    protected function callProtected(Object $object, string $method, array $params)
    {
        $class = new \ReflectionClass($object);
        $methodPtr = $class->getMethod($method);
        $methodPtr->setAccessible(true);
        return $methodPtr->invokeArgs($object, $params);
    }
}
