<?php
/**
 * CompanyContact Entity.
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
use MLInvoice\Database\Repository\CompanyContactRepository;
use MLInvoice\Database\Entity\Company;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * CompanyContact Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CompanyContactRepository::class)]
#[ORM\Table(name: 'company_contact')]
class CompanyContact implements EntityInterface, SoftDeleteInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var ?null */
    protected ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    /** @var bool */
    protected bool $deleted = false;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    /** @var Company|null */
    protected ?Company $company = null;

    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: ContactTagLink::class, cascade: ['persist','remove'])]
    /** @var Collection<int, ContactTagLink> */
    protected Collection $tagLinks;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $contactPerson = null;

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
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     *
     *
     * @param Company|null $c
     *
     * @return static
     */
    public function setCompany(?Company $c): static
    {
        $this->company = $c; return $this;
    }

    /**
     *
     *
     * @return Collection<int, ContactTagLink>
     */
    public function getTagLinks(): Collection
    {
        return $this->tagLinks;
    }

    /**
     *
     *
     * @param ContactTagLink $l
     *
     * @return static
     */
    public function addTagLink(ContactTagLink $l): self { if (! $this->tagLinks->contains($l))
    {
        $this->tagLinks->add($l); $l->setContact($this); } return $this;
    }

    /**
     *
     *
     * @param ContactTagLink $l
     *
     * @return static
     */
    public function removeTagLink(ContactTagLink $l): self { if ($this->tagLinks->removeElement($l))
    {
        $l->setContact(null); } return $this;
    }

    public function __construct()
    {
        $this->tagLinks = new ArrayCollection();
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
}
