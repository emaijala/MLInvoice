<?php
/**
 * InvoiceState Entity.
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
use MLInvoice\Database\Repository\InvoiceStateRepository;

/**
 * InvoiceState Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: InvoiceStateRepository::class)]
#[ORM\Table(name: 'invoice_state')]
class InvoiceState implements EntityInterface, SoftDeleteInterface
{
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
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * Order number
     *
     * @var ?int
     */
    #[ORM\Column(name: 'order_no', type: 'integer', nullable: true)]
    protected ?int $orderNo = null;

    /**
     * Is invoice in this state open?
     *
     * @var bool
     */
    #[ORM\Column(name: 'invoice_open', type: 'boolean')]
    protected bool $open = false;

    /**
     * Is invoice in this state open?
     *
     * @var bool
     */
    #[ORM\Column(name: 'invoice_unpaid', type: 'boolean')]
    protected bool $unpaid = false;

    /**
     * Is invoice in this state an offer?
     *
     * @var bool
     */
    #[ORM\Column(name: 'invoice_offer', type: 'boolean')]
    protected bool $offer = false;

    /**
     * Is invoice in this state "offer sent"?
     *
     * @var bool
     */
    #[ORM\Column(name: 'invoice_offer_sent', type: 'boolean')]
    protected bool $offerSent = false;

    /**
     * Is invoice in this state a template?
     *
     * @var bool
     */
    #[ORM\Column(name: 'invoice_template', type: 'boolean')]
    protected bool $template = false;

    /**
     * Mapping to Invoices
     *
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'state', targetEntity: Invoice::class, cascade: ['persist','remove'])]
    protected Collection $invoices;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->invoices = new ArrayCollection();
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
     * Get order number.
     *
     * @return
     */
    public function getOrderNo(): ?int
    {
        return $this->orderNo;
    }

    /**
     * Set order number.
     *
     * @param ?int $v Value
     */
    public function setOrderNo(?int $v): static
    {
        $this->orderNo = $v;
        return $this;
    }

    /**
     * Get invoice open flag.
     *
     * @return bool
     */
    public function getOpen(): bool
    {
        return $this->open;
    }

    /**
     * Set open flag.
     *
     * @param bool $v Value
     */
    public function setOpen(bool $v): static
    {
        $this->open = $v;
        return $this;
    }

    /**
     * Get unpaid flag.
     *
     * @return bool
     */
    public function getUnpaid(): bool
    {
        return $this->unpaid;
    }

    /**
     * Set unpaid flag.
     *
     * @param bool $v Value
     */
    public function setUnpaid(bool $v): static
    {
        $this->unpaid = $v;
        return $this;
    }

    /**
     * Get offer flag.
     *
     * @return bool
     */
    public function getOffer(): bool
    {
        return $this->offer;
    }

    /**
     * Set offer flag.
     *
     * @param bool $v Value
     */
    public function setOffer(bool $v): static
    {
        $this->offer = $v;
        return $this;
    }

    /**
     * Get offer sent flag.
     *
     * @return bool
     */
    public function getOfferSent(): bool
    {
        return $this->offerSent;
    }

    /**
     * Set offer sent flag.
     *
     * @param bool $v Value
     */
    public function setOfferSent(bool $v): static
    {
        $this->offerSent = $v;
        return $this;
    }

    /**
     * Get template flag.
     *
     * @return bool
     */
    public function getTemplate(): bool
    {
        return $this->template;
    }

    /**
     * Set template flag.
     *
     * @param bool $v Value
     */
    public function setTemplate(bool $v): static
    {
        $this->template = $v;
        return $this;
    }

    /**
     * Get invoices.
     *
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }
}
