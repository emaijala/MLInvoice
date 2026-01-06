<?php
/**
 * Company Entity.
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
use MLInvoice\Database\Entity\CompanyType;
use MLInvoice\Database\Repository\CompanyRepository;

/**
 * Company Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'company')]
class Company implements ExchangeArrayInterface
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

    #[ORM\Column(type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $insideInfo = null;

    #[ORM\ManyToOne(targetEntity: CompanyType::class, inversedBy: 'companies')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: true)]
    /** @var CompanyType|null */
    protected ?CompanyType $type = null;

    #[ORM\Column(type: 'string', length: 100)]
    /** @var string */
    protected string $companyName = '';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $contactPerson = null;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Invoice::class, cascade: ['persist','remove'])]
    /** @var Collection<int, Invoice> */
    protected Collection $invoices;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CompanyContact::class, cascade: ['persist','remove'])]
    /** @var Collection<int, CompanyContact> */
    protected Collection $contacts;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CompanyTagLink::class, cascade: ['persist','remove'])]
    /** @var Collection<int, CompanyTagLink> */
    protected Collection $tagLinks;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CustomPrice::class, cascade: ['persist','remove'])]
    /** @var Collection<int, CustomPrice> */
    protected Collection $customPrices;

    /**
     * Get id.
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Is deleted flag.
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Set deleted flag.
     * @param bool $v
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
    public function getInsideInfo(): ?string
    {
        return $this->insideInfo;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setInsideInfo(?string $v): static
    {
        $this->insideInfo = $v; return $this;
    }

    /**
     *
     *
     * @return CompanyType|null
     */
    public function getType(): ?CompanyType
    {
        return $this->type;
    }

    /**
     *
     *
     * @param CompanyType|null $v
     *
     * @return static
     */
    public function setType(?CompanyType $v): static
    {
        $this->type = $v; return $this;
    }

    /** Backwards-compatible: get numeric type id */
    public function getTypeId(): ?int
    {
        return $this->type?->getId();
    }

    /**
     *
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     *
     *
     * @param string $n
     *
     * @return static
     */
    public function setCompanyName(string $n): static
    {
        $this->companyName = $n; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setContactPerson(?string $v): static
    {
        $this->contactPerson = $v; return $this;
    }

    /**
     *
     *
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /**
     *
     *
     * @param Invoice $i
     *
     * @return static
     */
    public function addInvoice(Invoice $i): self { if (! $this->invoices->contains($i))
    {
        $this->invoices->add($i); $i->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param Invoice $i
     *
     * @return static
     */
    public function removeInvoice(Invoice $i): self { if ($this->invoices->removeElement($i))
    {
        $i->setCompany(null); } return $this;
    }

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->tagLinks = new ArrayCollection();
        $this->customPrices = new ArrayCollection();
    }

    /**
     *
     *
     * @return Collection<int, CompanyContact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    /**
     *
     *
     * @param CompanyContact $c
     *
     * @return static
     */
    public function addContact(CompanyContact $c): self { if (! $this->contacts->contains($c))
    {
        $this->contacts->add($c); $c->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param CompanyContact $c
     *
     * @return static
     */
    public function removeContact(CompanyContact $c): self { if ($this->contacts->removeElement($c))
    {
        $c->setCompany(null); } return $this;
    }

    /**
     *
     *
     * @return Collection<int, CompanyTagLink>
     */
    public function getTagLinks(): Collection
    {
        return $this->tagLinks;
    }

    /**
     *
     *
     * @param CompanyTagLink $l
     *
     * @return static
     */
    public function addTagLink(CompanyTagLink $l): self { if (! $this->tagLinks->contains($l))
    {
        $this->tagLinks->add($l); $l->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param CompanyTagLink $l
     *
     * @return static
     */
    public function removeTagLink(CompanyTagLink $l): self { if ($this->tagLinks->removeElement($l))
    {
        $l->setCompany(null); } return $this;
    }

    /**
     *
     *
     * @return Collection<int, CustomPrice>
     */
    public function getCustomPrices(): Collection
    {
        return $this->customPrices;
    }

    /**
     *
     *
     * @param CustomPrice $p
     *
     * @return static
     */
    public function addCustomPrice(CustomPrice $p): self { if (! $this->customPrices->contains($p))
    {
        $this->customPrices->add($p); $p->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param CustomPrice $p
     *
     * @return static
     */
    public function removeCustomPrice(CustomPrice $p): self { if ($this->customPrices->removeElement($p))
    {
        $p->setCompany(null); } return $this;
    }
}
