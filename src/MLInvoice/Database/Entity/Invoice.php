<?php
/**
 * Invoice Entity.
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLInvoice\Database\Feature\DateTimeTrait;
use MLInvoice\Database\Repository\InvoiceRepository;

/**
 * Invoice Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
class Invoice implements EntityInterface, SoftDeleteInterface, ExchangeArrayInterface
{
    use DateTimeTrait;
    use ExchangeArrayTrait;

    /**
     * ID
     *
     * @var ?int
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
     * Name
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    protected ?string $name = null;

    /**
     * Company
     *
     * @var ?Company
     */
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: true)]
    protected ?Company $company = null;

    /**
     * Invoice number
     *
     * @var ?string
     */
    #[ORM\Column(name: 'invoice_no', type: 'string', length: 100, nullable: true)]
    protected ?string $invoiceNo = null;

    /**
     * Invoice date
     *
     * @var ?int
     */
    #[ORM\Column(name: 'invoice_date', type: 'integer', nullable: true)]
    protected ?int $invoiceDate = null;

    /**
     * Due date
     *
     * @var ?int
     */
    #[ORM\Column(name: 'due_date', type: 'integer', nullable: true)]
    protected ?int $dueDate = null;

    /**
     * Payment date
     *
     * @var ?int
     */
    #[ORM\Column(name: 'payment_date', type: 'integer', nullable: true)]
    protected ?int $paymentDate = null;

    /**
     * Reference number
     *
     * @var ?string
     */
    #[ORM\Column(name: 'ref_number', type: 'string', length: 100, nullable: true)]
    protected ?string $refNumber = null;

    /**
     * Invoice state
     *
     * @var ?InvoiceState
     */
    #[ORM\ManyToOne(targetEntity: InvoiceState::class)]
    #[ORM\JoinColumn(name: 'state_id', referencedColumnName: 'id', nullable: true)]
    protected ?InvoiceState $state = null;

    /**
     * Reference
     *
     * @var ?string
     */
    #[ORM\Column(name: 'reference', type: 'string', length: 100, nullable: true)]
    protected ?string $reference = null;

    /**
     * Base
     *
     * @var ?Base
     */
    #[ORM\ManyToOne(targetEntity: Base::class)]
    #[ORM\JoinColumn(name: 'base_id', referencedColumnName: 'id', nullable: true)]
    protected ?Base $base = null;

    /**
     * Refunded invoice
     *
     * @var ?Invoice
     */
    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(name: 'refunded_invoice_id', referencedColumnName: 'id', nullable: true)]
    protected ?Invoice $refundedInvoice = null;

    /**
     * Print date
     *
     * @var ?int
     */
    #[ORM\Column(name: 'print_date', type: 'integer', nullable: true)]
    protected ?int $printDate = null;

    /**
     * Archived flag
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $archived = false;

    /**
     * Public info
     *
     * @var ?string
     */
    #[ORM\Column(name: 'info', type: 'text', nullable: true)]
    protected ?string $info = null;

    /**
     * Internal info
     *
     * @var ?string
     */
    #[ORM\Column(name: 'internal_info', type: 'text', nullable: true)]
    protected ?string $internalInfo = null;

    /**
     * Interval type
     *
     * @var int
     */
    #[ORM\Column(name: 'interval_type', type: 'integer')]
    protected int $intervalType = 0;

    /**
     * Next interval date
     *
     * @var ?int
     */
    #[ORM\Column(name: 'next_interval_date', type: 'integer', nullable: true)]
    protected ?int $nextIntervalDate = null;

    /**
     * Delivery terms
     *
     * @var ?DeliveryTerms
     */
    #[ORM\ManyToOne(targetEntity: DeliveryTerms::class)]
    #[ORM\JoinColumn(name: 'delivery_terms_id', referencedColumnName: 'id', nullable: true)]
    protected ?DeliveryTerms $deliveryTerms = null;

    /**
     * Delivery method
     *
     * @var ?DeliveryMethod
     */
    #[ORM\ManyToOne(targetEntity: DeliveryMethod::class)]
    #[ORM\JoinColumn(name: 'delivery_method_id', referencedColumnName: 'id', nullable: true)]
    protected ?DeliveryMethod $deliveryMethod = null;

    /**
     * Foreword
     *
     * @var ?string
     */
    #[ORM\Column(name: 'foreword', type: 'text', nullable: true)]
    protected ?string $foreword = null;

    /**
     * Afterword
     *
     * @var ?string
     */
    #[ORM\Column(name: 'afterword', type: 'text', nullable: true)]
    protected ?string $afterword = null;

    /**
     * Delivery time
     *
     * @var ?string
     */
    #[ORM\Column(name: 'delivery_time', type: 'string', length: 100, nullable: true)]
    protected ?string $deliveryTime = null;

    /**
     * Delivery address
     *
     * @var ?string
     */
    #[ORM\Column(name: 'delivery_address', type: 'text', nullable: true)]
    protected ?string $deliveryAddress = null;

    /**
     * UUID
     *
     * @var ?string
     */
    #[ORM\Column(name: 'uuid', type: 'string', length: 50, nullable: true)]
    protected ?string $uuid = null;

    /**
     * Type
     *
     * @var ?InvoiceType
     */
    #[ORM\ManyToOne(targetEntity: InvoiceType::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: true)]
    protected ?InvoiceType $type = null;

    /**
     * Source recurring invoice template
     *
     * @var ?Invoice
     */
    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(name: 'template_invoice_id', referencedColumnName: 'id', nullable: true)]
    protected ?Invoice $templateInvoice = null;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceRow::class, cascade: ['persist', 'remove'])]
    /** @var Collection<int, InvoiceRow> */
    protected Collection $rows;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceAttachment::class, cascade: ['persist', 'remove'])]
    /** @var Collection<int, InvoiceAttachment> */
    protected Collection $attachments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rows = new ArrayCollection();
        $this->attachments = new ArrayCollection();
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
     * Get name.
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setName(?string $v): static
    {
        $this->name = $v;
        return $this;
    }

    /**
     * Get company.
     *
     * @return ?Company
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Set company.
     *
     * @param ?Company $c New value
     *
     * @return static
     */
    public function setCompany(?Company $c): static
    {
        $this->company = $c;
        return $this;
    }

    /**
     * Get invoice number.
     *
     * @return ?string
     */
    public function getInvoiceNo(): ?string
    {
        return $this->invoiceNo;
    }

    /**
     * Set invoice number.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setInvoiceNo(?string $v): static
    {
        $this->invoiceNo = $v;
        return $this;
    }

    /**
     * Get invoice date.
     *
     * @return ?DateTime
     */
    public function getInvoiceDate(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->invoiceDate);
    }

    /**
     * Set invoice date.
     *
     * @param ?DateTime $d New value
     *
     * @return static
     */
    public function setInvoiceDate(?DateTime $d): static
    {
        $this->invoiceDate = $this->getDbFormatFromDateTime($d);
        return $this;
    }

    /**
     * Get due date.
     *
     * @return ?DateTime
     */
    public function getDueDate(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->dueDate);
    }

    /**
     * Set due date.
     *
     * @param ?DateTime $d New value
     *
     * @return static
     */
    public function setDueDate(?DateTime $d): static
    {
        $this->dueDate = $this->getDbFormatFromDateTime($d);
        return $this;
    }

    /**
     * Get payment date.
     *
     * @return ?DateTime
     */
    public function getPaymentDate(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->paymentDate);
    }

    /**
     * Set payment date.
     *
     * @param ?DateTime $d New value
     *
     * @return static
     */
    public function setPaymentDate(?DateTime $d): static
    {
        $this->paymentDate = $this->getDbFormatFromDateTime($d);
        return $this;
    }

    /**
     * Get reference number.
     *
     * @return ?string
     */
    public function getReferenceNo(): ?string
    {
        return $this->refNumber;
    }

    /**
     * Set reference number.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setReferenceNo(?string $v): static
    {
        $this->refNumber = $v;
        return $this;
    }

    /**
     * Get state.
     *
     * @return ?InvoiceState
     */
    public function getState(): ?InvoiceState
    {
        return $this->state;
    }

    /**
     * Set state.
     *
     * @param ?InvoiceState $s New value
     *
     * @return static
     */
    public function setState(?InvoiceState $s): static
    {
        $this->state = $s;
        return $this;
    }

    /**
     * Get reference.
     *
     * @return ?string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * Set reference.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setReference(?string $v): static
    {
        $this->reference = $v;
        return $this;
    }

    /**
     * Get base.
     *
     * @return ?Base
     */
    public function getBase(): ?Base
    {
        return $this->base;
    }

    /**
     * Set base.
     *
     * @param ?Base $b New value
     *
     * @return static
     */
    public function setBase(?Base $b): static
    {
        $this->base = $b;
        return $this;
    }

    /**
     * Get refunded invoice.
     *
     * @return ?Invoice
     */
    public function getRefundedInvoice(): ?Invoice
    {
        return $this->refundedInvoice;
    }

    /**
     * Set refunded invoice.
     *
     * @param ?Invoice $i New value
     *
     * @return static
     */
    public function setRefundedInvoice(?Invoice $i): static
    {
        $this->refundedInvoice = $i;
        return $this;
    }

    /**
     * Get print date.
     *
     * @return ?DateTime
     */
    public function getPrintDate(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->printDate);
    }

    /**
     * Set print date.
     *
     * @param ?DateTime $d New value
     *
     * @return static
     */
    public function setPrintDate(?DateTime $d): static
    {
        $this->printDate = $this->getDbFormatFromDateTime($d);
        return $this;
    }

    /**
     * Get archived flag.
     *
     * @return bool
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

    /**
     * Set archived flag.
     *
     * @param bool $v New value
     *
     * @return static
     */
    public function setArchived(bool $v): static
    {
        $this->archived = $v;
        return $this;
    }

    /**
     * Get public info.
     *
     * @return ?string
     */
    public function getInfo(): ?string
    {
        return $this->info;
    }

    /**
     * Set public info.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setInfo(?string $v): static
    {
        $this->info = $v;
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
     * Get repeat interval type.
     *
     * @return int
     */
    public function getIntervalType(): int
    {
        return $this->intervalType;
    }

    /**
     * Set repeat interval type.
     *
     * @param int $v New value
     *
     * @return static
     */
    public function setIntervalType(int $v): static
    {
        $this->intervalType = $v;
        return $this;
    }

    /**
     * Get next interval date.
     *
     * @return ?DateTime
     */
    public function getNextIntervalDate(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->nextIntervalDate);
    }

    /**
     * Set next interval date.
     *
     * @param ?DateTime $d New value
     *
     * @return static
     */
    public function setNextIntervalDate(?DateTime $d): static
    {
        $this->nextIntervalDate = $this->getDbFormatFromDateTime($d);
        return $this;
    }

    /**
     * Get delivery terms.
     *
     * @return ?DeliveryTerms
     */
    public function getDeliveryTerms(): ?DeliveryTerms
    {
        return $this->deliveryTerms;
    }

    /**
     * Set delivery terms.
     *
     * @param ?DeliveryTerms $d New value
     *
     * @return static
     */
    public function setDeliveryTerms(?DeliveryTerms $d): static
    {
        $this->deliveryTerms = $d;
        return $this;
    }

    /**
     * Get delivery method.
     *
     * @return ?DeliveryMethod
     */
    public function getDeliveryMethod(): ?DeliveryMethod
    {
        return $this->deliveryMethod;
    }

    /**
     * Set delivery method.
     *
     * @param ?DeliveryMethod $d New value
     *
     * @return static
     */
    public function setDeliveryMethod(?DeliveryMethod $d): static
    {
        $this->deliveryMethod = $d;
        return $this;
    }

    /**
     * Get foreword.
     *
     * @return ?string
     */
    public function getForeword(): ?string
    {
        return $this->foreword;
    }

    /**
     * Set foreword.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setForeword(?string $v): static
    {
        $this->foreword = $v;
        return $this;
    }

    /**
     * Get afterword.
     *
     * @return ?string
     */
    public function getAfterword(): ?string
    {
        return $this->afterword;
    }

    /**
     * Set afterword.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setAfterword(?string $v): static
    {
        $this->afterword = $v;
        return $this;
    }

    /**
     * Get delivery time.
     *
     * @return ?string
     */
    public function getDeliveryTime(): ?string
    {
        return $this->deliveryTime;
    }

    /**
     * Set delivery time.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setDeliveryTime(?string $v): static
    {
        $this->deliveryTime = $v;
        return $this;
    }

    /**
     * Get delivery address.
     *
     * @return ?string
     */
    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    /**
     * Set delivery address.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setDeliveryAddress(?string $v): static
    {
        $this->deliveryAddress = $v;
        return $this;
    }

    /**
     * Get UUID.
     *
     * @return ?string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Set UUID.
     *
     * @param ?string $v New value
     *
     * @return static
     */
    public function setUuid(?string $v): static
    {
        $this->uuid = $v;
        return $this;
    }

    /**
     * Get type.
     *
     * @return ?InvoiceType
     */
    public function getType(): ?InvoiceType
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param ?InvoiceType $t New value
     *
     * @return static
     */
    public function setType(?InvoiceType $t): static
    {
        $this->type = $t;
        return $this;
    }

    /**
     * Get template invoice.
     *
     * @return ?Invoice
     */
    public function getTemplateInvoice(): ?Invoice
    {
        return $this->templateInvoice;
    }

    /**
     * Set template invoice.
     *
     * @param ?Invoice $i New value
     *
     * @return static
     */
    public function setTemplateInvoice(?Invoice $i): static
    {
        $this->templateInvoice = $i;
        return $this;
    }

    /**
     * Get invoice rows.
     *
     * @return Collection<int, InvoiceRow>
     */
    public function getRows(): Collection
    {
        return $this->rows;
    }

    /**
     * Add a row.
     *
     * @param InvoiceRow $r New row
     *
     * @return static
     */
    public function addRow(InvoiceRow $r): static
    {
        if (!$this->rows->contains($r)) {
            $this->rows->add($r);
            $r->setInvoice($this);
        }
        return $this;
    }

    /**
     * Remove a row.
     *
     * @param InvoiceRow $r Row to remove
     *
     * @return static
     */
    public function removeRow(InvoiceRow $r): static
    {
        if ($this->rows->removeElement($r)) {
            $r->setInvoice(null);
        }
        return $this;
    }

    /**
     * Get attachments.
     *
     * @return Collection<int, InvoiceAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /**
     * Add an attachment.
     *
     * @param InvoiceAttachment $a New attachment
     *
     * @return static
     */
    public function addAttachment(InvoiceAttachment $a): static
    {
        if (!$this->attachments->contains($a)) {
            $this->attachments->add($a);
            $a->setInvoice($this);
        }
        return $this;
    }

    /**
     * Remove an attachment.
     *
     * @param InvoiceAttachment $a Attachment to remove
     *
     * @return static
     */
    public function removeAttachment(InvoiceAttachment $a): static
    {
        if ($this->attachments->removeElement($a)) {
            $a->setInvoice(null);
        }
        return $this;
    }

    /**
     * Advance the next interval date of a recurring invoice or template
     *
     * @return void
     */
    function advanceInvoiceIntervalDate(): void
    {
        $next = match ($this->getIntervalType()) {
            // 1 month:
            2 => '+1 month',
            // 1 year:
            3 => '+1 year',
            // 2 - 6 months
            4,5,6,7,8 => '+' . ($this->getIntervalType() - 2) . ' months',
            // 2 years:
            14 => '+2 years',
            // 3 years:
            15 => '+2 years',
            default => null,
        };
        if (null !== $next) {
            $this->setNextIntervalDate(new DateTime($next));
        }
    }
}
