<?php
/**
 * CompanyTagLink Entity.
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
use MLInvoice\Database\Repository\CompanyTagLinkRepository;
use MLInvoice\Database\Entity\CompanyTag;
use MLInvoice\Database\Entity\Company;

/**
 * CompanyTagLink Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CompanyTagLinkRepository::class)]
#[ORM\Table(name: 'company_tag_link')]
class CompanyTagLink implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var ?null */
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CompanyTag::class, inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    /** @var CompanyTag|null */
    protected ?CompanyTag $tag = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'tagLinks')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    /** @var Company|null */
    protected ?Company $company = null;

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
     * @return CompanyTag|null
     */
    public function getTag(): ?CompanyTag
    {
        return $this->tag;
    }

    /**
     *
     *
     * @param CompanyTag|null $t
     *
     * @return static
     */
    public function setTag(?CompanyTag $t): static
    {
        $this->tag = $t; return $this;
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
}
