<?php
/**
 * CompanyTag Entity.
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
use MLInvoice\Database\Repository\CompanyTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * CompanyTag Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CompanyTagRepository::class)]
#[ORM\Table(name: 'company_tag')]
class CompanyTag
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $tag = null;

    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: CompanyTagLink::class, cascade: ['persist','remove'])]
    /** @var Collection<int, CompanyTagLink> */
    protected Collection $links;

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
     * @return ?string
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setTag(?string $v): static
    {
        $this->tag = $v; return $this;
    }

    /**
     *
     *
     * @return Collection<int, CompanyTagLink>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    /**
     *
     *
     * @param CompanyTagLink $l
     *
     * @return static
     */
    public function addLink(CompanyTagLink $l): self { if (! $this->links->contains($l))
    {
        $this->links->add($l); $l->setTag($this); } return $this;
    }

    /**
     *
     *
     * @param CompanyTagLink $l
     *
     * @return static
     */
    public function removeLink(CompanyTagLink $l): self { if ($this->links->removeElement($l))
    {
        $l->setTag(null); } return $this;
    }

    public function __construct()
    {
        $this->links = new ArrayCollection();
    }
}
