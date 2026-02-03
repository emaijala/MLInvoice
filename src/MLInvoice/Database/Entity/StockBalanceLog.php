<?php
/**
 * StockBalanceLog Entity.
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
use MLInvoice\Database\Repository\StockBalanceLogRepository;

/**
 * StockBalanceLog Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: StockBalanceLogRepository::class)]
#[ORM\Table(name: 'stock_balance_log')]
class StockBalanceLog implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var ?null */
    protected ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    /** @var \DateTime|null */
    protected ?\DateTime $time = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    /** @var User|null */
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'stockBalanceLogs')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    /** @var Product|null */
    protected ?Product $product = null;

    #[ORM\Column(name: 'stock_change', type: 'decimal', precision: 11, scale: 2)]
    /** @var string */
    protected string $stockChange = '0.00';

    #[ORM\Column(type: 'string', length: 255)]
    /** @var string */
    protected string $description = '';

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
     * @return \DateTime|null
     */
    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    /**
     *
     *
     * @param \DateTime|null $t
     *
     * @return static
     */
    public function setTime(?\DateTime $t): static
    {
        $this->time = $t; return $this;
    }

    /**
     *
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     *
     *
     * @param User|null $u
     *
     * @return static
     */
    public function setUser(?User $u): static
    {
        $this->user = $u; return $this;
    }

    /**
     *
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     *
     *
     * @param Product|null $p
     *
     * @return static
     */
    public function setProduct(?Product $p): static
    {
        $this->product = $p; return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getStockChange(): string
    {
        return $this->stockChange;
    }

    /**
     *
     *
     * @param string $v
     *
     * @return static
     */
    public function setStockChange(string $v): static
    {
        $this->stockChange = $v; return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     *
     *
     * @param string $v
     *
     * @return static
     */
    public function setDescription(string $v): static
    {
        $this->description = $v; return $this;
    }
}
