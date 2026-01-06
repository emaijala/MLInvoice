<?php
/**
 * ContactTagLink Entity.
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
use MLInvoice\Database\Repository\ContactTagLinkRepository;
use MLInvoice\Database\Entity\ContactTag;
use MLInvoice\Database\Entity\CompanyContact;

/**
 * ContactTagLink Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: ContactTagLinkRepository::class)]
#[ORM\Table(name: 'contact_tag_link')]
class ContactTagLink
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContactTag::class, inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    /** @var ContactTag|null */
    protected ?ContactTag $tag = null;

    #[ORM\ManyToOne(targetEntity: CompanyContact::class, inversedBy: 'tagLinks')]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id')]
    /** @var CompanyContact|null */
    protected ?CompanyContact $contact = null;

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
     * @return ContactTag|null
     */
    public function getTag(): ?ContactTag
    {
        return $this->tag;
    }

    /**
     *
     *
     * @param ContactTag|null $t
     *
     * @return static
     */
    public function setTag(?ContactTag $t): static
    {
        $this->tag = $t; return $this;
    }

    /**
     *
     *
     * @return CompanyContact|null
     */
    public function getContact(): ?CompanyContact
    {
        return $this->contact;
    }

    /**
     *
     *
     * @param CompanyContact|null $c
     *
     * @return static
     */
    public function setContact(?CompanyContact $c): static
    {
        $this->contact = $c; return $this;
    }
}
