<?php
/**
 * DefaultValue Entity.
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
use MLInvoice\Database\Repository\DefaultValueRepository;

/**
 * DefaultValue Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: DefaultValueRepository::class)]
#[ORM\Table(name: 'default_value')]
class DefaultValue
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    /** @var bool */
    protected bool $deleted = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $name = null;

    #[ORM\Column(name: 'order_no', type: 'integer', nullable: true)]
    /** @var int|null */
    protected ?int $orderNo = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $type = null;

    #[ORM\Column(type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $content = null;

    #[ORM\Column(type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $additional = null;

    /**
     *
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     *
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     *
     *
     * @param bool $v
     *
     * @return static
     */
    public function setDeleted(bool $v): static
    {
        $this->deleted = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setName(?string $v): static
    {
        $this->name = $v; return $this;
    }

    /**
     *
     *
     * @return ?int
     */
    public function getOrderNo(): ?int
    {
        return $this->orderNo;
    }

    /**
     *
     *
     * @param int|null $v
     *
     * @return static
     */
    public function setOrderNo(?int $v): static
    {
        $this->orderNo = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setType(?string $v): static
    {
        $this->type = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setContent(?string $v): static
    {
        $this->content = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getAdditional(): ?string
    {
        return $this->additional;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setAdditional(?string $v): static
    {
        $this->additional = $v; return $this;
    }
}
