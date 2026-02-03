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
use MLInvoice\Database\Repository\RowTypeRepository;

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
class Product implements EntityInterface, SoftDeleteInterface
{
    /**
     * ID
     *
     * @var ?null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * Deleted flag
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $deleted = false;

    /**
     * Product name
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 100)]
    protected string $productName = '';

    /**
     * Description
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $description = null;

    /**
     * Product code
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $productCode = null;

    /**
     * Product group
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $productGroup = null;

    /**
     * First barcode
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $barcode1 = null;

    /**
     * Type of first barcode
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    protected ?string $barcode1Type = null;

    /**
     * Second barcode
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $barcode2 = null;

    /**
     * Type of second barcode
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    protected ?string $barcode2Type = null;

    /**
     * Internal info
     *
     * @var ?string
     */
    #[ORM\Column(name: 'internal_info', type: 'text', nullable: true)]
    protected ?string $internalInfo = null;

    /**
     * Unit price
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    protected ?string $unitPrice = null;

    /**
     * Purchase price
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    protected ?string $purchasePrice = null;

    /**
     * Row type
     *
     * @var ?RowType
     */
    #[ORM\ManyToOne(targetEntity: RowTypeRepository::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: true)]
    protected ?RowType $type = null;

    /**
     * VAT percent
     *
     * @var string
     */
    #[ORM\Column(type: 'decimal', precision: 9, scale: 1)]
    protected string $vatPercent = '0';

    /**
     * VAT included flag
     *
     * @var bool
     */
    #[ORM\Column(name: 'vat_included', type: 'boolean')]
    protected bool $vatIncluded = false;

    /**
     * Discount
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    protected ?string $discount = null;

    /**
     * Discount amount
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    protected ?string $discountAmount = null;

    /**
     * Price decimals
     *
     * @var int
     */
    #[ORM\Column(type: 'decimal', precision: 1, scale: 0)]
    protected int $priceDecimals = 2;

    /**
     * Order number
     *
     * @var ?int
     */
    #[ORM\Column(name: 'order_no', type: 'integer', nullable: true)]
    protected ?int $orderNo;

    /**
     * Stock balance
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 11, scale: 2, nullable: true)]
    protected ?string $stockBalance = null;

    /**
     * Vendor
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $vendor = null;

    /**
     * Vendor's code
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $vendorsCode = null;

    /**
     * Weight
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    protected ?string $weight = null;

    /**
     * Stock balance log entries
     *
     * @var Collection<int, StockBalanceLog>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: StockBalanceLog::class, cascade: ['persist','remove'])]
    protected Collection $stockBalanceLogs;

    /**
     * Custom price maps.
     *
     * @var Collection<int, CustomPriceMap>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: CustomPriceMap::class, cascade: ['persist','remove'])]
    protected Collection $customPriceMaps;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->stockBalanceLogs = new ArrayCollection();
        $this->customPriceMaps = new ArrayCollection();
    }

    /**
     * Get ID.
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get deleted flag.
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Set deleted flag.
     *
     * @param bool $v New value
     *
     * @return static
     */
    public function setDeleted(bool $v): static
    {
        $this->deleted = $v;
        return $this;
    }

    /**
     * Get product name.
     *
     * @return string
     */
    public function getProductName(): string
    {
        return $this->productName;
    }

    /**
     * Set product name.
     *
     * @param string $v New value
     *
     * @return static
     */
    public function setProductName(string $v): static
    {
        $this->productName = $v;
        return $this;
    }

    /**
     * Get description.
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setDescription(?string $v): static
    {
        $this->description = $v;
        return $this;
    }

    /**
     * Get product code.
     *
     * @return ?string
     */
    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    /**
     * Set product code.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setProductCode(?string $v): static
    {
        $this->productCode = $v;
        return $this;
    }

    /**
     * Get product group.
     *
     * @return ?string
     */
    public function getProductGroup(): ?string
    {
        return $this->productGroup;
    }

    /**
     * Set product group.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setProductGroup(?string $v): static
    {
        $this->productGroup = $v;
        return $this;
    }

    /**
     * Get first barcode.
     *
     * @return ?string
     */
    public function getBarcode1(): ?string
    {
        return $this->barcode1;
    }

    /**
     * Set first barcode.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setBarcode1(?string $v): static
    {
        $this->barcode1 = $v;
        return $this;
    }

    /**
     * Get type of first barcode.
     *
     * @return ?string
     */
    public function getBarcode1Type(): ?string
    {
        return $this->barcode1Type;
    }

    /**
     * Set type of first barcode.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setBacode1Type(?string $v): static
    {
        $this->barcode1Type = $v;
        return $this;
    }

    /**
     * Get second barcode.
     *
     * @return ?string
     */
    public function getBarcode2(): ?string
    {
        return $this->barcode2;
    }

    /**
     * Set second barcode.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setBarcode2(?string $v): static
    {
        $this->barcode2 = $v;
        return $this;
    }

    /**
     * Get type of second barcode.
     *
     * @return ?string
     */
    public function getBarcode2Type(): ?string
    {
        return $this->barcode2Type;
    }

    /**
     * Set type of second barcode.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setBacode2Type(?string $v): static
    {
        $this->barcode2Type = $v;
        return $this;
    }

    /**
     * Get internal info.
     *
     * @return ?string
     */
    public function getInternalInfo(): ?string
    {
        return $this->internalInfo;
    }

    /**
     * Set internal info.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setInternalInfo(?string $v): static
    {
        $this->internalInfo = $v;
        return $this;
    }

    /**
     * Get unit price.
     *
     * @return ?string
     */
    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    /**
     * Set unit price.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setUnitPrice(?string $v): static
    {
        $this->unitPrice = $v;
        return $this;
    }

    /**
     * Get purchase price.
     *
     * @return ?string
     */
    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    /**
     * Set purchase price.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setPurchasePrice(?string $v): static
    {
        $this->purchasePrice = $v;
        return $this;
    }

    /**
     * Get row type.
     *
     * @return ?RowType
     */
    public function getType(): ?RowType
    {
        return $this->type;
    }

    /**
     * Set row type.
     *
     * @param ?RowType $t Type
     *
     * @return static
     */
    public function setType(?RowType $t): static
    {
        $this->type = $t;
        return $this;
    }

    /**
     * Get VAT percent.
     *
     * @return string
     */
    public function getVatPercent(): string
    {
        return $this->vatPercent;
    }

    /**
     * Set VAT percent.
     *
     * @param string $v VAT
     *
     * @return static
     */
    public function setVatPercent(string $v): static
    {
        $this->vatPercent = $v;
        return $this;
    }

    /**
     * Get VAT included flag.
     *
     * @return bool
     */
    public function getVatIncluded(): bool
    {
        return $this->vatIncluded;
    }

    /**
     * Set VAT included flag.
     *
     * @param  $v New value
     *
     * @return static
     */
    public function setVatIncluded($v): static
    {
        $this->vatIncluded = $v;
        return $this;
    }

    /**
     * Get discount.
     *
     * @return ?string
     */
    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    /**
     * Set discount.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setDiscount(?string $v): static
    {
        $this->discount = $v;
        return $this;
    }

    /**
     * Get discount amount.
     *
     * @return ?string
     */
    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    /**
     * Set discount amount.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setDiscountAmount(?string $v): static
    {
        $this->discountAmount = $v;
        return $this;
    }

    /**
     * Get price decimals.
     *
     * @return ?string
     */
    public function getPriceDecimals(): ?string
    {
        return $this->priceDecimals;
    }

    /**
     * Set price decimals.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setPriceDecimals(?string $v): static
    {
        $this->priceDecimals = $v;
        return $this;
    }

    /**
     * Get order number.
     *
     * @return ?int
     */
    public function getOrderNo(): ?int
    {
        return $this->orderNo;
    }

    /**
     * Set order number.
     *
     * @param ?int $v New value
     *
     * @return static
     */
    public function setOrderNo(?int $v): static
    {
        $this->orderNo = $v;
        return $this;
    }

    /**
     * Get stock balance.
     *
     * @return ?string
     */
    public function getStockBalance(): ?string
    {
        return $this->stockBalance;
    }

    /**
     * Set stock balance.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setStockBalance(?string $v): static
    {
        $this->stockBalance = $v;
        return $this;
    }

    /**
     * Get vendor.
     *
     * @return ?string
     */
    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    /**
     * Set vendor.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setVendor(?string $v): static
    {
        $this->vendor = $v;
        return $this;
    }

    /**
     * Get vendor's code.
     *
     * @return ?string
     */
    public function getVendorsCode(): ?string
    {
        return $this->vendorsCode;
    }

    /**
     * Set vendor's code.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setVendorsCode(?string $v): static
    {
        $this->vendorsCode = $v;
        return $this;
    }


    /**
     * Get stock balance log entries.
     *
     * @return Collection<int, StockBalanceLog>
     */
    public function getStockBalanceLogs(): Collection
    {
        return $this->stockBalanceLogs;
    }

    /**
     * Add stock balance log entry.
     *
     * @param StockBalanceLog $l
     *
     * @return static
     */
    public function addStockBalanceLog(StockBalanceLog $l): static
    {
        if (!$this->stockBalanceLogs->contains($l)) {
            $this->stockBalanceLogs->add($l);
            $l->setProduct($this);
        }
        return $this;
    }

    /**
     * Remove a stock balance log entry.
     *
     * @param StockBalanceLog $l Log entry
     *
     * @return static
     */
    public function removeStockBalanceLog(StockBalanceLog $l): static
    {
        if ($this->stockBalanceLogs->removeElement($l)) {
            $l->setProduct(null);
        }
        return $this;
    }

    /**
     * Get custom price maps.
     *
     * @return Collection<int, CustomPriceMap>
     */
    public function getCustomPriceMaps(): Collection
    {
        return $this->customPriceMaps;
    }

    /**
     * Add a custom price map.
     *
     * @param CustomPriceMap $m Map
     *
     * @return static
     */
    public function addCustomPriceMap(CustomPriceMap $m): static
    {
        if (!$this->customPriceMaps->contains($m)) {
            $this->customPriceMaps->add($m);
            $m->setProduct($this);
        }
        return $this;
    }

    /**
     * Remove a custom price map.
     *
     * @param CustomPriceMap $m Map
     *
     * @return static
     */
    public function removeCustomPriceMap(CustomPriceMap $m): static
    {
        if ($this->customPriceMaps->removeElement($m)) {
            $m->setProduct(null);
        }
        return $this;
    }
}
