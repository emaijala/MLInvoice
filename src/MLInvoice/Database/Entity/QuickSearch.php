<?php
/**
 * QuickSearch Entity.
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
use MLInvoice\Database\Repository\QuickSearchRepository;

/**
 * QuickSearch Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: QuickSearchRepository::class)]
#[ORM\Table(name: 'quicksearch')]
class QuickSearch implements EntityInterface
{
    /**
     * ID
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * User
     *
     * @var User|null
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quicksearches')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;

    /**
     * Name
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * Function
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $func = null;

    /**
     * Form
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $form = null;

    /**
     * Query
     *
     * @var ?string
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $whereclause = null;

    /**
     * Public flag
     *
     * @var bool
     */
    #[ORM\Column(name: 'public', type: 'boolean')]
    protected bool $public = false;

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
     * Get user.
     *
     * @return ?User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @param ?User $u User
     *
     * @return static
     */
    public function setUser(?User $u): static
    {
        $this->user = $u;
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
     * @param ?string $v Name
     *
     * @return static
     */
    public function setName(?string $v): static
    {
        $this->name = $v; return $this;
    }

    /**
     * Get function.
     *
     * @return ?string
     */
    public function getFunc(): ?string
    {
        $func = $this->func;
        if ('companies' === $func) {
            $func = 'company';
        } elseif (str_ends_with($func, 's')) {
            $func = substr($func, 0, -1);
        }
        return $func;
    }

    /**
     * Set function.
     *
     * @param ?string $v Function
     *
     * @return static
     */
    public function setFunc(?string $v): static
    {
        $this->func = $v;
        return $this;
    }

    /**
     * Get form.
     *
     * @return ?string
     */
    public function getForm(): ?string
    {
        return $this->form;
    }

    /**
     * Set form.
     *
     * @param ?string $v Form
     *
     * @return static
     */
    public function setForm(?string $v): static
    {
        $this->form = $v;
        return $this;
    }

    /**
     * Get JSON query.
     *
     * @return ?string
     */
    public function getQuery(): ?string
    {
        return $this->whereclause;
    }

    /**
     * Set JSON query.
     *
     * @param ?string $v Query
     *
     * @return static
     */
    public function setQuery(?string $v): static
    {
        $this->whereclause = $v;
        return $this;
    }

    /**
     * Get public flag.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * Set public flag.
     *
     * @param bool $v Public
     *
     * @return static
     */
    public function setPublic(bool $v): static
    {
        $this->public = $v;
        return $this;
    }
}
