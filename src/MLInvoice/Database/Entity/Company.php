<?php
/**
 * Company Entity.
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
use MLInvoice\Database\Entity\CompanyType;
use MLInvoice\Database\Repository\CompanyRepository;

/**
 * Company Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'company')]
class Company implements EntityInterface, SoftDeleteInterface, ExchangeArrayInterface
{
    use ExchangeArrayTrait;

    /**
     * ID
     *
     * @var ?null
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
     * Inside info
     *
     * @var ?string
     */
    #[ORM\Column(name: 'inside_info', type: 'text', nullable: true)]
    protected ?string $insideInfo = null;

    /**
     * Company type
     *
     * @var ?CompanyType
     */
    #[ORM\ManyToOne(targetEntity: CompanyType::class, inversedBy: 'companies')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', nullable: true)]
    protected ?CompanyType $type = null;

    /**
     * Company name
     *
     * @var string
     */
    #[ORM\Column(name: 'company_name', type: 'string', length: 100)]
    protected string $companyName = '';

    /**
     * Contact person
     *
     * @var ?string
     */
    #[ORM\Column(name: 'contact_person', type: 'string', length: 100, nullable: true)]
    protected ?string $contactPerson = null;

    /**
     * Street address
     *
     * @var ?string
     */
    #[ORM\Column(name: 'street_address', type: 'string', length: 100, nullable: true)]
    protected ?string $streetAddress = null;

    /**
     * Zip code
     *
     * @var ?string
     */
    #[ORM\Column(name: 'zip_code', type: 'string', length: 10, nullable: true)]
    protected ?string $zipCode = null;

    /**
     * City
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $city = null;

    /**
     * Country
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $country = null;

    /**
     * Phone
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    protected ?string $phone = null;

    /**
     * Fax
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    protected ?string $fax = null;

    /**
     * Email
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    protected ?string $email = null;

    /**
     * Mobile
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    protected ?string $gsm = null;

    /**
     * Billing address
     *
     * @var ?string
     */
    #[ORM\Column(name: 'billing_address', type: 'text', nullable: true)]
    protected ?string $billingAddress = null;

    /**
     * Delivery address
     *
     * @var ?string
     */
    #[ORM\Column(name: 'delivery_address', type: 'text', nullable: true)]
    protected ?string $deliveryAddress = null;

    /**
     * Web site
     *
     * @var ?string
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    protected ?string $www = null;

    /**
     * Info
     *
     * @var ?string
     */
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $info = null;

    /**
     * Company ID
     *
     * @var ?string
     */
    #[ORM\Column(name: 'company_id', type: 'string', length: 15, nullable: true)]
    protected ?string $companyId = null;

    /**
     * Organisation unit number
     *
     * @var ?string
     */
    #[ORM\Column(name: 'org_unit_number', type: 'string', length: 35, nullable: true)]
    protected ?string $orgUnitNumber = null;

    /**
     * Payment intermediator
     *
     * @var ?string
     */
    #[ORM\Column(name: 'payment_intermediator', type: 'string', length: 100, nullable: true)]
    protected ?string $paymentIntermediator = null;

    /**
     * Customer number
     *
     * @var ?int
     */
    #[ORM\Column(name: 'customer_no', type: 'integer', nullable: true)]
    protected ?string $customerNo = null;

    /**
     * Default reference number
     *
     * @var ?string
     */
    #[ORM\Column(name: 'default_ref_number', type: 'string', length: 100, nullable: true)]
    protected ?string $defaultRefNumber = null;

    /**
     * Inactive flag
     *
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $inactive = false;

    /**
     * Delivery terms
     *
     * @var ?DeliveryTerms
     */
    #[ORM\ManyToOne(targetEntity: DeliveryTerms::class)]
    #[ORM\JoinColumn(name: 'delivery_terms_id', referencedColumnName: 'id', nullable: true)]
    protected ?DeliveryTerms $deliveryTerms = null;

    /**
     * Delivery method
     *
     * @var ?DeliveryMethod
     */
    #[ORM\ManyToOne(targetEntity: DeliveryMethod::class)]
    #[ORM\JoinColumn(name: 'delivery_method_id', referencedColumnName: 'id', nullable: true)]
    protected ?DeliveryMethod $deliveryMethod = null;

    /**
     * Payment days
     *
     * @var ?int
     */
    #[ORM\Column(name: 'payment_days', type: 'integer', nullable: true)]
    protected ?int $paymentDays = null;

    /**
     * Terms of payment
     *
     * @var ?string
     */
    #[ORM\Column(name: 'terms_of_payment', type: 'string', length: 255, nullable: true)]
    protected ?string $termsOfPayment = null;

    /**
     * Invoice VAT-less flag
     *
     * @var bool
     */
    #[ORM\Column(name: 'invoice_vatless', type: 'boolean')]
    protected bool $invoiceVatless = false;

    /**
     * Invoice default foreword
     *
     * @var ?string
     */
    #[ORM\Column(name: 'invoice_default_foreword', type: 'text', nullable: true)]
    protected ?string $invoiceDefaultForeword = null;

    /**
     * Invoice default afterword
     *
     * @var ?string
     */
    #[ORM\Column(name: 'invoice_default_afterword', type: 'text', nullable: true)]
    protected ?string $invoiceDefaultAfterword = null;

    /**
     * Offer default foreword
     *
     * @var ?string
     */
    #[ORM\Column(name: 'offer_default_foreword', type: 'text', nullable: true)]
    protected ?string $offerDefaultForeword = null;

    /**
     * Offer default afterword
     *
     * @var ?string
     */
    #[ORM\Column(name: 'offer_default_afterword', type: 'text', nullable: true)]
    protected ?string $offerDefaultAfterword = null;

    /**
     * Invoice default reference
     *
     * @var ?string
     */
    #[ORM\Column(name: 'invoice_default_reference', type: 'string', length: 100, nullable: true)]
    protected ?string $invoiceDefaultReference = null;

    /**
     * Invoices
     *
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Invoice::class, cascade: ['persist','remove'])]
    protected Collection $invoices;

    /**
     * Contacts
     *
     * @var Collection<int, CompanyContact>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CompanyContact::class, cascade: ['persist','remove'])]
    protected Collection $contacts;

    /**
     * Tags
     *
     * @var Collection<int, CompanyTagLink>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CompanyTagLink::class, cascade: ['persist','remove'])]
    protected Collection $tagLinks;

    /**
     * Custom prices
     *
     * @var Collection<int, CustomPrice>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: CustomPrice::class, cascade: ['persist','remove'])]
    protected Collection $customPrices;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->invoices = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->tagLinks = new ArrayCollection();
        $this->customPrices = new ArrayCollection();
    }

    /**
     * Get id.
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
     * @return static
     */
    public function setDeleted(bool $v): static
    {
        $this->deleted = $v; return $this;
    }

    /**
     * Get inside info.
     *
     * @return ?string
     */
    public function getInsideInfo(): ?string
    {
        return $this->insideInfo;
    }

    /**
     * Set inside info.
     *
     * @param ?string $v Info
     *
     * @return static
     */
    public function setInsideInfo(?string $v): static
    {
        $this->insideInfo = $v;
        return $this;
    }

    /**
     * Get company type.
     *
     * @return ?CompanyType
     */
    public function getType(): ?CompanyType
    {
        return $this->type;
    }

    /**
     * Set company type.
     *
     * @param ?CompanyType $v Type
     *
     * @return static
     */
    public function setType(?CompanyType $v): static
    {
        $this->type = $v;
        return $this;
    }

    /**
     * Backwards-compatible: get numeric type id
     *
     * @return ?int
     */
    public function getTypeId(): ?int
    {
        return $this->type?->getId();
    }

    /**
     * Get company name.
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * Set company name.
     *
     * @param string $n Name
     *
     * @return static
     */
    public function setCompanyName(string $n): static
    {
        $this->companyName = $n;
        return $this;
    }

    /**
     * Get contact person.
     *
     * @return ?string
     */
    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    /**
     * Set contact person.
     *
     * @param ?string $v Contact person
     *
     * @return static
     */
    public function setContactPerson(?string $v): static
    {
        $this->contactPerson = $v;
        return $this;
    }

    /**
     * Get street address.
     *
     * @return ?string
     */
    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    /**
     * Set street address.
     *
     * @param ?string $v Address
     *
     * @return static
     */
    public function setStreetAddress(?string $v): static
    {
        $this->streetAddress = $v;
        return $this;
    }

    /**
     * Get zip code.
     *
     * @return ?string
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * Set zip code.
     *
     * @param ?string $v Zip code
     *
     * @return static
     */
    public function setZipCode(?string $v): static
    {
        $this->zipCode = $v;
        return $this;
    }

    /**
     * Get city.
     *
     * @return ?string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Set city.
     *
     * @param ?string $v City
     *
     * @return static
     */
    public function setCity(?string $v): static
    {
        $this->city = $v;
        return $this;
    }

    /**
     * Get country.
     *
     * @return ?string
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Set country.
     *
     * @param ?string $v Country
     *
     * @return static
     */
    public function setCountry(?string $v): static
    {
        $this->country = $v;
        return $this;
    }

    /**
     * Get phone.
     *
     * @return ?string
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Set phone.
     *
     * @param ?string $v Phone
     *
     * @return static
     */
    public function setPhone(?string $v): static
    {
        $this->phone = $v;
        return $this;
    }

    /**
     * Get fax.
     *
     * @return ?string
     */
    public function getFax(): ?string
    {
        return $this->fax;
    }

    /**
     * Set fax.
     *
     * @param ?string $v Fax
     *
     * @return static
     */
    public function setFax(?string $v): static
    {
        $this->fax = $v;
        return $this;
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
        $this->email = $v;
        return $this;
    }

    /**
     * Get mobile phone.
     *
     * @return ?string
     */
    public function getGsm(): ?string
    {
        return $this->gsm;
    }

    /**
     * Set mobile phone.
     *
     * @param ?string $v Phone
     *
     * @return static
     */
    public function setGsm(?string $v): static
    {
        $this->gsm = $v;
        return $this;
    }

    /**
     * Get billing address.
     *
     * @return ?string
     */
    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    /**
     * Set billing address.
     *
     * @param ?string $v Billing address
     *
     * @return static
     */
    public function setBillingAddress(?string $v): static
    {
        $this->billingAddress = $v;
        return $this;
    }

    /**
     * Get delivery address.
     *
     * @return ?string
     */
    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    /**
     * Set delivery address.
     *
     * @param ?string $v Delivery address
     *
     * @return static
     */
    public function setDeliveryAddress(?string $v): static
    {
        $this->deliveryAddress = $v;
        return $this;
    }

    /**
     * Get web site.
     *
     * @return ?string
     */
    public function getWww(): ?string
    {
        return $this->www;
    }

    /**
     * Set web site.
     *
     * @param ?string $v Web site
     *
     * @return static
     */
    public function setWww(?string $v): static
    {
        $this->www = $v;
        return $this;
    }

    /**
     * Get info.
     *
     * @return ?string
     */
    public function getInfo(): ?string
    {
        return $this->info;
    }

    /**
     * Set info.
     *
     * @param ?string $v Info
     *
     * @return static
     */
    public function setInfo(?string $v): static
    {
        $this->info = $v;
        return $this;
    }

    /**
     * Get company ID.
     *
     * @return ?string
     */
    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }

    /**
     * Set company ID.
     *
     * @param ?string $v ID
     *
     * @return static
     */
    public function setCompanyId(?string $v): static
    {
        $this->companyId = $v;
        return $this;
    }

    /**
     * Get organisational unit number.
     *
     * @return ?string
     */
    public function getOrgUnitNumber(): ?string
    {
        return $this->orgUnitNumber;
    }

    /**
     * Set organisational unit number.
     *
     * @param ?string $v Number
     *
     * @return static
     */
    public function setOrgUnitNumber(?string $v): static
    {
        $this->orgUnitNumber = $v;
        return $this;
    }

    /**
     * Get payment intermediator.
     *
     * @return ?string
     */
    public function getPaymentIntermediator(): ?string
    {
        return $this->paymentIntermediator;
    }

    /**
     * Set payment intermediator.
     *
     * @param ?string $v Intermediator
     *
     * @return static
     */
    public function setPaymentIntermediator(?string $v): static
    {
        $this->paymentIntermediator = $v;
        return $this;
    }

    /**
     * Get customer number.
     *
     * @return ?int
     */
    public function getCustomerNo(): ?int
    {
        return $this->customerNo;
    }

    /**
     * Set customer number.
     *
     * @param ?int $v Number
     *
     * @return static
     */
    public function setCustomerNo(?int $v): static
    {
        $this->customerNo = $v;
        return $this;
    }

    /**
     * Get default reference number.
     *
     * @return ?string
     */
    public function getDefaultRefNumber(): ?string
    {
        return $this->defaultRefNumber;
    }

    /**
     * Set default reference number.
     *
     * @param ?string $v Number
     *
     * @return static
     */
    public function setDefaultRefNumber(?string $v): static
    {
        $this->defaultRefNumber = $v;
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
     * @param bool $v Flag
     *
     * @return static
     */
    public function setInactive(bool $v): static
    {
        $this->inactive = $v;
        return $this;
    }

    /**
     * Get delivery terms.
     *
     * @return ?DeliveryTerms
     */
    public function getDeliveryTerms(): ?DeliveryTerms
    {
        return $this->deliveryTerms;
    }

    /**
     * Set delivery terms.
     *
     * @param ?DeliveryTerms $v Terms
     *
     * @return static
     */
    public function setDeliveryTerms(?DeliveryTerms $v): static
    {
        $this->deliveryTerms = $v;
        return $this;
    }

    /**
     * Get delivery method.
     *
     * @return ?DeliveryMethod
     */
    public function getDeliveryMethod(): ?DeliveryMethod
    {
        return $this->deliveryMethod;
    }

    /**
     * Set delivery method.
     *
     * @param ?DeliveryMethod $v Method
     *
     * @return static
     */
    public function setDeliveryMethod(?DeliveryMethod $v): static
    {
        $this->deliveryMethod = $v;
        return $this;
    }

    /**
     * Get payment days.
     *
     * @return ?int
     */
    public function getPaymentDays(): ?int
    {
        return $this->paymentDays;
    }

    /**
     * Set payment days.
     *
     * @param ?int $v Days
     *
     * @return static
     */
    public function setPaymentDays(?int $v): static
    {
        $this->paymentDays = $v;
        return $this;
    }

    /**
     * Get terms of payment.
     *
     * @return ?string
     */
    public function getTermsOfPayment(): ?string
    {
        return $this->termsOfPayment;
    }

    /**
     * Set terms of payment.
     *
     * @param ?string $v Terms
     *
     * @return static
     */
    public function setTermsOfPayment(?string $v): static
    {
        $this->termsOfPayment = $v;
        return $this;
    }

    /**
     * Get invoice VAT-less flag.
     *
     * @return bool
     */
    public function getInvoiceVatless(): bool
    {
        return $this->invoiceVatless;
    }

    /**
     * Set invoice VAT-less flag.
     *
     * @param bool $v Flag
     *
     * @return static
     */
    public function setInvoiceVatless(bool $v): static
    {
        $this->invoiceVatless = $v;
        return $this;
    }

    /**
     * Get invoice default foreword.
     *
     * @return ?string
     */
    public function getInvoiceDefaultForeword(): ?string
    {
        return $this->invoiceDefaultForeword;
    }

    /**
     * Set invoice default foreword.
     *
     * @param ?string $v Default
     *
     * @return static
     */
    public function setInvoiceDefaultForeword(?string $v): static
    {
        $this->invoiceDefaultForeword = $v;
        return $this;
    }

    /**
     * Get invoice default afterword.
     *
     * @return ?string
     */
    public function getInvoiceDefaultAfterword(): ?string
    {
        return $this->invoiceDefaultAfterword;
    }

    /**
     * Set invoice default afterword.
     *
     * @param ?string $v Default
     *
     * @return static
     */
    public function setInvoiceDefaultAfterword(?string $v): static
    {
        $this->invoiceDefaultAfterword = $v;
        return $this;
    }

    /**
     * Get offer default foreword.
     *
     * @return ?string
     */
    public function getOfferDefaultForeword(): ?string
    {
        return $this->offerDefaultForeword;
    }

    /**
     * Set offer default foreword.
     *
     * @param ?string $v Default
     *
     * @return static
     */
    public function setOfferDefaultForeword(?string $v): static
    {
        $this->offerDefaultForeword = $v;
        return $this;
    }

    /**
     * Get offer default afterword.
     *
     * @return ?string
     */
    public function getOfferDefaultAfterword(): ?string
    {
        return $this->offerDefaultAfterword;
    }

    /**
     * Set offer default afterword.
     *
     * @param ?string $v Default
     *
     * @return static
     */
    public function setOfferDefaultAfterword(?string $v): static
    {
        $this->offerDefaultAfterword = $v;
        return $this;
    }

    /**
     * Get invoice default reference.
     *
     * @return ?string
     */
    public function getInvoiceDefaultReference(): ?string
    {
        return $this->invoiceDefaultReference;
    }

    /**
     * Set invoice default reference.
     *
     * @param ?string $v Reference
     *
     * @return static
     */
    public function setInvoiceDefaultReference(?string $v): static
    {
        $this->invoiceDefaultReference = $v;
        return $this;
    }

    /**
     * Get invoices
     *
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /**
     *
     *
     * @return Collection<int, CompanyContact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    /**
     *
     *
     * @param CompanyContact $c
     *
     * @return static
     */
    public function addContact(CompanyContact $c): self { if (! $this->contacts->contains($c))
    {
        $this->contacts->add($c); $c->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param CompanyContact $c
     *
     * @return static
     */
    public function removeContact(CompanyContact $c): self { if ($this->contacts->removeElement($c))
    {
        $c->setCompany(null); } return $this;
    }

    /**
     *
     *
     * @return Collection<int, CompanyTagLink>
     */
    public function getTagLinks(): Collection
    {
        return $this->tagLinks;
    }

    /**
     *
     *
     * @param CompanyTagLink $l
     *
     * @return static
     */
    public function addTagLink(CompanyTagLink $l): self { if (! $this->tagLinks->contains($l))
    {
        $this->tagLinks->add($l); $l->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param CompanyTagLink $l
     *
     * @return static
     */
    public function removeTagLink(CompanyTagLink $l): self { if ($this->tagLinks->removeElement($l))
    {
        $l->setCompany(null); } return $this;
    }

    /**
     *
     *
     * @return Collection<int, CustomPrice>
     */
    public function getCustomPrices(): Collection
    {
        return $this->customPrices;
    }

    /**
     *
     *
     * @param CustomPrice $p
     *
     * @return static
     */
    public function addCustomPrice(CustomPrice $p): self { if (! $this->customPrices->contains($p))
    {
        $this->customPrices->add($p); $p->setCompany($this); } return $this;
    }

    /**
     *
     *
     * @param CustomPrice $p
     *
     * @return static
     */
    public function removeCustomPrice(CustomPrice $p): self { if ($this->customPrices->removeElement($p))
    {
        $p->setCompany(null); } return $this;
    }
}
