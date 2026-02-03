<?php
/**
 * SendApiConfig Entity.
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
use MLInvoice\Database\Repository\SendApiConfigRepository;

/**
 * SendApiConfig Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: SendApiConfigRepository::class)]
#[ORM\Table(name: 'send_api_config')]
class SendApiConfig implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var ?null */
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Base::class, inversedBy: 'sendApiConfigs')]
    #[ORM\JoinColumn(name: 'base_id', referencedColumnName: 'id')]
    /** @var Base|null */
    protected ?Base $base = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $method = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $username = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $reference = null;

    #[ORM\Column(name: 'post_class', type: 'boolean')]
    /** @var bool */
    protected bool $postClass = false;

    #[ORM\Column(name: 'add_to_queue', type: 'boolean')]
    /** @var bool */
    protected bool $addToQueue = false;

    #[ORM\Column(name: 'finvoice_mail_backup', type: 'boolean')]
    /** @var bool */
    protected bool $finvoiceMailBackup = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $directory = null;

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
     * @return Base|null
     */
    public function getBase(): ?Base
    {
        return $this->base;
    }

    /**
     *
     *
     * @param Base|null $b
     *
     * @return static
     */
    public function setBase(?Base $b): static
    {
        $this->base = $b; return $this;
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
     * @return ?string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setMethod(?string $v): static
    {
        $this->method = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setUsername(?string $v): static
    {
        $this->username = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setPassword(?string $v): static
    {
        $this->password = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setReference(?string $v): static
    {
        $this->reference = $v; return $this;
    }

    /**
     *
     *
     * @return bool
     */
    public function isPostClass(): bool
    {
        return $this->postClass;
    }

    /**
     *
     *
     * @param bool $v
     *
     * @return static
     */
    public function setPostClass(bool $v): static
    {
        $this->postClass = $v; return $this;
    }

    /**
     *
     *
     * @return bool
     */
    public function isAddToQueue(): bool
    {
        return $this->addToQueue;
    }

    /**
     *
     *
     * @param bool $v
     *
     * @return static
     */
    public function setAddToQueue(bool $v): static
    {
        $this->addToQueue = $v; return $this;
    }

    /**
     *
     *
     * @return bool
     */
    public function isFinvoiceMailBackup(): bool
    {
        return $this->finvoiceMailBackup;
    }

    /**
     *
     *
     * @param bool $v
     *
     * @return static
     */
    public function setFinvoiceMailBackup(bool $v): static
    {
        $this->finvoiceMailBackup = $v; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setDirectory(?string $v): static
    {
        $this->directory = $v; return $this;
    }
}
