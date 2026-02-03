<?php
/**
 * CustomPriceMap Entity.
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
use MLInvoice\Database\Repository\CustomPriceMapRepository;
use MLInvoice\Database\Entity\CustomPrice;
use MLInvoice\Database\Entity\Product;

/**
 * CustomPriceMap Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CustomPriceMapRepository::class)]
#[ORM\Table(name: 'custom_price_map')]
class CustomPriceMap implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var ?null */
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomPrice::class, inversedBy: 'maps')]
    #[ORM\JoinColumn(name: 'custom_price_id', referencedColumnName: 'id')]
    /** @var CustomPrice|null */
    protected ?CustomPrice $customPrice = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'customPriceMaps')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    /** @var Product|null */
    protected ?Product $product = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 5, nullable: true)]
    /** @var ?string */
    protected ?string $unitPrice = null;

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
     * @return CustomPrice|null
     */
    public function getCustomPrice(): ?CustomPrice
    {
        return $this->customPrice;
    }

    /**
     *
     *
     * @param CustomPrice|null $c
     *
     * @return static
     */
    public function setCustomPrice(?CustomPrice $c): static
    {
        $this->customPrice = $c; return $this;
    }

    /**
     *
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     *
     *
     * @param Product|null $p
     *
     * @return static
     */
    public function setProduct(?Product $p): static
    {
        $this->product = $p; return $this;
    }

    /**
     *
     *
     * @return ?string
     */
    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    /**
     *
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setUnitPrice(?string $v): static
    {
        $this->unitPrice = $v; return $this;
    }
}
