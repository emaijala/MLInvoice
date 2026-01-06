<?php
/**
 * CompanyType Entity.
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
use MLInvoice\Database\Repository\CompanyTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLInvoice\Database\Entity\Company;

/**
 * CompanyType Entity.
  *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
*/
#[ORM\Entity(repositoryClass: CompanyTypeRepository::class)]
#[ORM\Table(name: 'company_type')]
class CompanyType
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

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Company::class, cascade: ['persist','remove'])]
    /** @var Collection<int, Company> */
    protected Collection $companies;

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
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    /**
     *
     *
     * @param Company $c
     *
     * @return static
     */
    public function addCompany(Company $c): self { if (! $this->companies->contains($c))
    {
        $this->companies->add($c); $c->setType($this); } return $this;
    }

    /**
     *
     *
     * @param Company $c
     *
     * @return static
     */
    public function removeCompany(Company $c): self { if ($this->companies->removeElement($c))
    {
        $c->setType(null); } return $this;
    }

    public function __construct()
    {
        $this->companies = new ArrayCollection();
    }
}
