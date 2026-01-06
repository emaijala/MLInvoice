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

use Doctrine\ORM\Mapping as ORM;
use MLInvoice\Database\Repository\InvoiceRowRepository;

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
class InvoiceRow implements ExchangeArrayInterface
{
    use ExchangeArrayTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    /** @var bool */
    protected bool $deleted = false;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'rows')]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    /** @var Invoice|null */
    protected ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'invoiceRows')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: true)]
    /** @var Product|null */
    protected ?Product $product = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 9, scale: 2, nullable: true)]
    /** @var ?string */
    protected ?string $pcs = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    /** @var ?string */
    protected ?string $price = null;

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
     * @return Invoice|null
     */
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    /**
     *
     *
     * @param Invoice|null $i
     *
     * @return static
     */
    public function setInvoice(?Invoice $i): static
    {
        $this->invoice = $i; return $this;
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
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     *
     *
     * @param ?string $d
     *
     * @return static
     */
    public function setDescription(?string $d): static
    {
        $this->description = $d; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getPcs(): ?string
    {
        return $this->pcs;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setPcs(?string $v): static
    {
        $this->pcs = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setPrice(?string $v): static
    {
        $this->price = $v; return $this;
    }
}
