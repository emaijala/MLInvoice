<?php
/**
 * SessionType Entity.
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

namespace MLInvoice\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use MLInvoice\Database\Feature\DateTimeTrait;
use MLInvoice\Database\Repository\SessionTypeRepository;

/**
 * SessionType Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: SessionTypeRepository::class)]
#[ORM\Table(name: 'session_type')]
class SessionType
{
    use DateTimeTrait;

    /**
     * Session type ID.
     *
     * @var ?int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    protected ?int $id;

    /**
     * Deleted?
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $deleted = false;

    /**
     * Name
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * Order
     *
     * @var ?int
     */
    #[ORM\Column(name: 'order_no', type: 'integer')]
    protected ?int $orderNo;

    /**
     * Timeout
     *
     * @var ?int
     */
    #[ORM\Column(name: 'time_out', type: 'integer')]
    protected ?int $timeout;

    /**
     * Access level
     *
     * @var ?int
     */
    #[ORM\Column(name: 'access_level', type: 'integer')]
    protected ?int $accessLevel;

    /**
     * Get ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set ID.
     *
     * @param string $id ID
     *
     * @return static
     */
    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get access level.
     *
     * @return ?int
     */
    public function getAccessLevel(): ?int
    {
        return $this->accessLevel;
    }
}
