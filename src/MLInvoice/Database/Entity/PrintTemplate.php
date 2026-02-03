<?php
/**
 * PrintTmplate Entity.
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
use MLInvoice\Database\Repository\PrintTemplateRepository;

/**
 * PrintTemplate Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: PrintTemplateRepository::class)]
#[ORM\Table(name: 'print_template')]
class PrintTemplate implements EntityInterface, SoftDeleteInterface
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
     * Deleted flag
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $deleted = false;

    /**
     * Name
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 100)]
    protected string $name = '';

    /**
     * Filename
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $filename = null;

    /**
     * Parameters
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $parameters = null;

    /**
     * Output filename
     *
     * @var ?string
     */
    #[ORM\Column(name: 'output_filename', type: 'string', length: 255, nullable: true)]
    protected ?string $outputFilename = null;

    /**
     * Type
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 100)]
    protected string $type = '';

    /**
     * Order number
     *
     * @var ?int
     */
    #[ORM\Column(name: 'order_no', type: 'integer', nullable: true)]
    protected ?int $orderNo = null;

    /**
     * Open in new window?
     *
     * @var bool
     */
    #[ORM\Column(name: 'new_window', type: 'boolean')]
    protected bool $newWindow = false;

    /**
     * Inactive flag
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $inactive = false;

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
     * @param bool $v
     *
     * @return static
     */
    public function setDeleted(bool $v): static
    {
        $this->deleted = $v;
        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $v New value
     *
     * @return static
     */
    public function setName(string $v): static
    {
        $this->name = $v;
        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set filename.
     *
     * @param string $v New value
     *
     * @return static
     */
    public function setFilename(string $v): static
    {
        $this->filename = $v;
        return $this;
    }

    /**
     * Get parameters.
     *
     * @return string
     */
    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * Set parameters.
     *
     * @param string $v New value
     *
     * @return static
     */
    public function setParameters(string $v): static
    {
        $this->parameters = $v;
        return $this;
    }

    /**
     * Get output filename.
     *
     * @return string
     */
    public function getOutputFilename(): string
    {
        return $this->outputFilename;
    }

    /**
     * Set output filename.
     *
     * @param string $v New value
     *
     * @return static
     */
    public function setOutputFilename(string $v): static
    {
        $this->outputFilename = $v;
        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $v New value
     *
     * @return static
     */
    public function setType(string $v): static
    {
        $this->type = $v;
        return $this;
    }

    /**
     * Get order number.
     *
     * @return
     */
    public function getOrderNo(): ?int
    {
        return $this->orderNo;
    }

    /**
     * Set order number.
     *
     * @param ?int $v Value
     */
    public function setOrderNo(?int $v): static
    {
        $this->orderNo = $v;
        return $this;
    }

    /**
     * Get open in new window flag.
     *
     * @return bool
     */
    public function getNewWindow(): bool
    {
        return $this->newWindow;
    }

    /**
     * Set open in new window flag.
     *
     * @param bool $v Value
     */
    public function setNewWindow(bool $v): static
    {
        $this->newWindow = $v;
        return $this;
    }

    /**
     * Get inactive flag.
     *
     * @return bool
     */
    public function getInactive(): bool
    {
        return $this->inactive;
    }

    /**
     * Set inactive flag.
     *
     * @param bool $v Value
     */
    public function setInactive(bool $v): static
    {
        $this->inactive = $v;
        return $this;
    }
}
