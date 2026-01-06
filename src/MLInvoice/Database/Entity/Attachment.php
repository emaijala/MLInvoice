<?php
/**
 * Attachment Entity.
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
use MLInvoice\Database\Repository\AttachmentRepository;

/**
 * Attachment Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachment')]
class Attachment
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    /** @var string */
    protected string $name = '';

    #[ORM\Column(type: 'string', length: 255)]
    /** @var string */
    protected string $mimetype = '';

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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     *
     *
     * @param string $v
     *
     * @return static
     */
    public function setName(string $v): static
    {
        $this->name = $v; return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    /**
     *
     *
     * @param string $v
     *
     * @return static
     */
    public function setMimetype(string $v): static
    {
        $this->mimetype = $v; return $this;
    }
}
