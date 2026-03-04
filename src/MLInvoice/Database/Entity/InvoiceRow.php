<?php
/**
 * InvoiceRow Entity.
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

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use MLInvoice\Database\Feature\DateTimeTrait;
use MLInvoice\Database\Repository\InvoiceRowRepository;
use MLInvoice\Database\Repository\RowTypeRepository;

/**
 * InvoiceRow Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: InvoiceRowRepository::class)]
#[ORM\Table(name: 'invoice_row')]
class InvoiceRow implements EntityInterface, SoftDeleteInterface, ExchangeArrayInterface
{
    use DateTimeTrait;
    use ExchangeArrayTrait;

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
     * Invoice
     *
     * @var ?Invoice
     */
    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'rows')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Invoice $invoice = null;

    /**
     * Product
     *
     * @var ?Product
     */
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'invoiceRows')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: true)]
    protected ?Product $product = null;

    /**
     * Description
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $description = null;

    /**
     * Row type
     *
     * @var ?RowType
     */
    #[ORM\ManyToOne(targetEntity: RowType::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: true)]
    protected ?RowType $type = null;

    /**
     * Pieces
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 9, scale: 2, nullable: true)]
    protected ?string $pcs = null;

    /**
     * Price
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    protected ?string $price = null;

    /**
     * Date
     *
     * @var ?int
     */
    #[ORM\Column(name: 'row_date', type: 'integer', nullable: true)]
    protected ?int $date;

    /**
     * VAT percent
     *
     * @var string
     */
    #[ORM\Column(type: 'decimal', precision: 9, scale: 1)]
    protected string $vat = '0';

    /**
     * VAT included flag
     *
     * @var bool
     */
    #[ORM\Column(name: 'vat_included', type: 'boolean')]
    protected bool $vatIncluded = false;

    /**
     * Order number
     *
     * @var ?int
     */
    #[ORM\Column(name: 'order_no', type: 'integer', nullable: true)]
    protected ?int $orderNo;

    /**
     * Reminder row state
     *
     * @var bool
     */
    #[ORM\Column(name: 'reminder_row', type: 'integer')]
    protected int $reminder = 0;

    /**
     * Partial payment flag
     *
     * @var bool
     */
    #[ORM\Column(name: 'partial_payment', type: 'boolean')]
    protected bool $partialPayment = false;

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
    #[ORM\Column(name: 'discount_amount', type: 'decimal', precision: 15, scale: 5, nullable: true)]
    protected ?string $discountAmount = null;

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
     * Get invoice.
     *
     * @return ?Invoice
     */
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    /**
     * Set invoice.
     *
     * @param ?Invoice $i
     *
     * @return static
     */
    public function setInvoice(?Invoice $i): static
    {
        $this->invoice = $i;
        return $this;
    }

    /**
     * Get product.
     *
     * @return ?Product
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Set product.
     *
     * @param ?Product $p Product
     *
     * @return static
     */
    public function setProduct(?Product $p): static
    {
        $this->product = $p;
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
     * @param ?string $d Description
     *
     * @return static
     */
    public function setDescription(?string $d): static
    {
        $this->description = $d;
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
     * Get pieces.
     *
     * @return ?string
     */
    public function getPcs(): ?string
    {
        return $this->pcs;
    }

    /**
     * Set pieces.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setPcs(?string $v): static
    {
        $this->pcs = $v;
        return $this;
    }

    /**
     * Get price.
     *
     * @return ?string
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * Set price.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setPrice(?string $v): static
    {
        $this->price = $v;
        return $this;
    }

    /**
     * Get date.
     *
     * @return ?DateTime
     */
    public function getDate(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->date);
    }

    /**
     * Set date.
     *
     * @param ?DateTime $d New value
     *
     * @return static
     */
    public function setDate(?DateTime $d): static
    {
        $this->date = $this->getDbFormatFromDateTime($d);
        return $this;
    }

    /**
     * Get VAT percent.
     *
     * @return string
     */
    public function getVat(): string
    {
        return $this->vat;
    }

    /**
     * Set VAT percent.
     *
     * @param string $v VAT
     *
     * @return static
     */
    public function setVat(string $v): static
    {
        $this->vat = $v;
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
     * Get reminder row state.
     *
     * @return int
     */
    public function getReminder(): int
    {
        return $this->reminder;
    }

    /**
     * Set reminder row state.
     *
     * @param int $v New value
     *
     * @return static
     */
    public function setReminder(int $v): static
    {
        $this->reminder = $v;
        return $this;
    }

    /**
     * Get partial payment flag.
     *
     * @return bool
     */
    public function getPartialPayment(): bool
    {
        return $this->partialPayment;
    }

    /**
     * Set partial payment flag.
     *
     * @param bool $v New value
     *
     * @return static
     */
    public function setPartialPayment(bool $v): static
    {
        $this->partialPayment = $v;
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
     * Calculate row sum.
     *
     * @param array $row Row
     *
     * @return array Associative array with the following keys:
     * sum - sum excluding VAT
     * vat - VAT amount
     * sumVat - sum including VAT
     */
    function calculateRowSum(): array
    {
        $price = $this->getPrice();
        $count = $this->getPcs();
        $vat = $this->getVat();
        $vatIncluded = $this->getVatIncluded();
        $discount = $this->getDiscount();
        $discountAmount = $this->getDiscountAmount();

        if ($discount) {
            $price *= (1 - $discount / 100);
        }
        if ($discountAmount) {
            $price -= $discountAmount;
        }

        if ($vatIncluded) {
            $rowSumVAT = $count * $price;
            $rowSum = ($rowSumVAT / (1 + $vat / 100));
            $rowVAT = $rowSumVAT - $rowSum;
        } else {
            $rowSum = $count * $price;
            $rowVAT = ($rowSum * ($vat / 100));
            $rowSumVAT = $rowSum + $rowVAT;
        }
        return [
            'sum' => $rowSum,
            'vat' => $rowVAT,
            'sumVat' => $rowSumVAT,
        ];
    }
}
