<?php
/**
 * Product Entity.
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLInvoice\Database\Repository\ProductRepository;

/**
 * Product Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    /** @var bool */
    protected bool $deleted = false;

    #[ORM\Column(type: 'string', length: 100)]
    /** @var string */
    protected string $productName = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $description = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $productCode = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    /** @var ?string */
    protected ?string $unitPrice = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: InvoiceRow::class, cascade: ['persist','remove'])]
    /** @var Collection<int, InvoiceRow> */
    protected Collection $invoiceRows;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: StockBalanceLog::class, cascade: ['persist','remove'])]
    /** @var Collection<int, StockBalanceLog> */
    protected Collection $stockBalanceLogs;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: CustomPriceMap::class, cascade: ['persist','remove'])]
    /** @var Collection<int, CustomPriceMap> */
    protected Collection $customPriceMaps;

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
     * @return string
     */
    public function getProductName(): string
    {
        return $this->productName;
    }

    /**
     *
     *
     * @param string $v
     *
     * @return static
     */
    public function setProductName(string $v): static
    {
        $this->productName = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setDescription(?string $v): static
    {
        $this->description = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setProductCode(?string $v): static
    {
        $this->productCode = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setUnitPrice(?string $v): static
    {
        $this->unitPrice = $v; return $this;
    }

    /**
     *
     *
     * @return Collection<int, InvoiceRow>
     */
    public function getInvoiceRows(): Collection
    {
        return $this->invoiceRows;
    }

    /**
     *
     *
     * @param InvoiceRow $r
     *
     * @return static
     */
    public function addInvoiceRow(InvoiceRow $r): self { if (! $this->invoiceRows->contains($r))
    {
        $this->invoiceRows->add($r); $r->setProduct($this); } return $this;
    }

    /**
     *
     *
     * @param InvoiceRow $r
     *
     * @return static
     */
    public function removeInvoiceRow(InvoiceRow $r): self { if ($this->invoiceRows->removeElement($r))
    {
        $r->setProduct(null); } return $this;
    }

    /**
     *
     *
     * @return Collection<int, StockBalanceLog>
     */
    public function getStockBalanceLogs(): Collection
    {
        return $this->stockBalanceLogs;
    }

    /**
     *
     *
     * @param StockBalanceLog $l
     *
     * @return static
     */
    public function addStockBalanceLog(StockBalanceLog $l): self { if (! $this->stockBalanceLogs->contains($l))
    {
        $this->stockBalanceLogs->add($l); $l->setProduct($this); } return $this;
    }

    /**
     *
     *
     * @param StockBalanceLog $l
     *
     * @return static
     */
    public function removeStockBalanceLog(StockBalanceLog $l): self { if ($this->stockBalanceLogs->removeElement($l))
    {
        $l->setProduct(null); } return $this;
    }

    public function __construct()
    {
        $this->invoiceRows = new ArrayCollection();
        $this->stockBalanceLogs = new ArrayCollection();
        $this->customPriceMaps = new ArrayCollection();
    }

    /**
     *
     *
     * @return Collection<int, CustomPriceMap>
     */
    public function getCustomPriceMaps(): Collection
    {
        return $this->customPriceMaps;
    }

    /**
     *
     *
     * @param CustomPriceMap $m
     *
     * @return static
     */
    public function addCustomPriceMap(CustomPriceMap $m): self { if (! $this->customPriceMaps->contains($m))
    {
        $this->customPriceMaps->add($m); $m->setProduct($this); } return $this;
    }

    /**
     *
     *
     * @param CustomPriceMap $m
     *
     * @return static
     */
    public function removeCustomPriceMap(CustomPriceMap $m): self { if ($this->customPriceMaps->removeElement($m))
    {
        $m->setProduct(null); } return $this;
    }
}
