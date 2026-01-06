<?php
/**
 * User Entity.
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
use MLInvoice\Database\Repository\UserRepository;

/**
 * User Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
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
     * Deleted?
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
     * Email
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    protected ?string $email = null;

    /**
     * Login (username)
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $login = null;

    /**
     * Password
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $passwd = null;

    /**
     * Session type (access level)
     *
     * @var ?SessionType
     */
    #[ORM\ManyToOne(targetEntity: SessionType::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id')]
    protected ?SessionType $sessionType = null;

    /**
     * Token
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $token = null;

    /**
     * Quick searches
     *
     * @var Collection<int, QuickSearch>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: QuickSearch::class, cascade: ['persist','remove'])]
    protected Collection $quicksearches;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->quicksearches = new ArrayCollection();
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
     * @param bool $v Deleted flag
     *
     * @return static
     */
    public function setDeleted(bool $v): static
    {
        $this->deleted = $v; return $this;
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
     * @param ?string $v Name
     *
     * @return static
     */
    public function setName(?string $v): static
    {
        $this->name = $v; return $this;
    }

    /**
     * Get email.
     *
     * @return ?string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param ?string $v Email
     *
     * @return static
     */
    public function setEmail(?string $v): static
    {
        $this->email = $v; return $this;
    }

    /**
     * Get login (username).
     *
     * @return ?string
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * Set login (username)
     *
     * @param ?string $v Login
     *
     * @return static
     */
    public function setLogin(?string $v): static
    {
        $this->login = $v; return $this;
    }

    /**
     * Get password.
     *
     * @return ?string
     */
    public function getPasswd(): ?string
    {
        return $this->passwd;
    }

    /**
     * Set password.
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setPasswd(?string $v): static
    {
        $this->passwd = $v; return $this;
    }

    /**
     * Get session type.
     *
     * @return ?SessionType
     */
    public function getSessionType(): ?SessionType
    {
        return $this->sessionType;
    }

    /**
     * Set session type.
     *
     * @param ?SessionType $v Session type
     *
     * @return static
     */
    public function setSessionType(?SessionType $v): static
    {
        $this->sessionType = $v;
        return $this;
    }

    /**
     * Get quick searches.
     *
     * @return Collection<int, QuickSearch>
     */
    public function getQuickSearches(): Collection
    {
        return $this->quicksearches;
    }

    /**
     * Add a quick search.
     *
     * @param QuickSearch $q
     *
     * @return static
     */
    public function addQuickSearch(QuickSearch $q): self { if (! $this->quicksearches->contains($q))
    {
        $this->quicksearches->add($q); $q->setUser($this); } return $this;
    }

    /**
     * Remove a quick search.
     *
     * @param QuickSearch $q
     *
     * @return static
     */
    public function removeQuickSearch(QuickSearch $q): self { if ($this->quicksearches->removeElement($q))
    {
        $q->setUser(null); } return $this;
    }
}
