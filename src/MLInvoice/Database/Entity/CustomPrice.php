<?php
/**
 * CustomPrice Entity.
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

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use MLInvoice\Database\Repository\CustomPriceRepository;
use MLInvoice\Database\Entity\Company;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MLInvoice\Database\Feature\DateTimeTrait;

/**
 * CustomPrice Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CustomPriceRepository::class)]
#[ORM\Table(name: 'custom_price')]
class CustomPrice implements EntityInterface
{
    use DateTimeTrait;

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
     * Company
     *
     * @var ?Company
     */
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'customPrices')]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    protected ?Company $company = null;

    /**
     * Discount
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    protected ?string $discount = null;

    /**
     * Multiplier
     *
     * @var ?string
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 5, nullable: true)]
    protected ?string $multiplier = null;

    /**
     * Valid until date
     *
     * @var ?int
     */
    #[ORM\Column(type: 'int', nullable: true)]
    protected ?int $validUntil = null;

    /**
     * Custom price maps
     *
     * @var Collection<int, CustomPriceMap>
     */
    #[ORM\OneToMany(mappedBy: 'customPrice', targetEntity: CustomPriceMap::class, cascade: ['persist','remove'])]
    protected Collection $maps;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->maps = new ArrayCollection();
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
     * Get company.
     *
     * @return ?Company
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Set company.
     *
     * @param ?Company $c Company
     *
     * @return static
     */
    public function setCompany(?Company $c): static
    {
        $this->company = $c;
        return $this;
    }

    /**
     * Get discount.
     *
     * @return ?string
     */
    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    /**
     * Set discount.
     *
     * @param ?string $v
     *
     * @return static
     */
    public function setDiscount(?string $v): static
    {
        $this->discount = $v;
        return $this;
    }

    /**
     * Get multiplier.
     *
     * @return ?string
     */
    public function getMultiplier(): ?string
    {
        return $this->multiplier;
    }

    /**
     * Set multiplier.
     *
     * @param ?string $v Multiplier
     *
     * @return static
     */
    public function setMultiplier(?string $v): static
    {
        $this->multiplier = $v;
        return $this;
    }

    /**
     * Get valid until date.
     *
     * @return ?DateTime
     */
    public function getValidUntil(): ?DateTime
    {
        return $this->getDateTimeFromDbFormat($this->validUntil);
    }

    /**
     * Set valid until date.
     *
     * @param ?DateTime $v Date
     *
     * @return static
     */
    public function setValidUntil(?string $v): static
    {
        $this->validUntil = $this->getDbFormatFromDateTime($v);
        return $this;
    }

    /**
     * Get validity.
     *
     * @return bool
     */
    public function getValid(): bool
    {
        $until = $this->getValidUntil();
        return null === $until || $until >= new DateTime('today');
    }

    /**
     * Get custom price maps.
     *
     * @return Collection<int, CustomPriceMap>
     */
    public function getMaps(): Collection
    {
        return $this->maps;
    }

    /**
     * Add a custom price map.
     *
     * @param CustomPriceMap $m Map to add
     *
     * @return static
     */
    public function addMap(CustomPriceMap $m): static
    {
        if (!$this->maps->contains($m)) {
            $this->maps->add($m);
            $m->setCustomPrice($this);
        } return $this;
    }

    /**
     * Remove a custom price map.
     *
     * @param CustomPriceMap $m Map to remove
     *
     * @return static
     */
    public function removeMap(CustomPriceMap $m): static
    {
        if ($this->maps->removeElement($m)) {
            $m->setCustomPrice(null);
        }
        return $this;
    }
}
