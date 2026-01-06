<?php
/**
 * (Billing) Base Entity.
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
use MLInvoice\Database\Repository\BaseRepository;

/**
 * (Billing) Base Entity.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
#[ORM\Entity(repositoryClass: BaseRepository::class)]
#[ORM\Table(name: 'base')]
class Base implements ExchangeArrayInterface
{
    use ExchangeArrayTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    /** @var int|null */
    protected ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    /** @var bool */
    protected bool $deleted = false;

    #[ORM\Column(type: 'boolean')]
    /** @var bool */
    protected bool $inactive = false;

    #[ORM\Column(type: 'string', length: 100)]
    /** @var string */
    protected string $name;

    #[ORM\Column(name: 'contact_person', type: 'string', length: 50)]
    /** @var string */
    protected string $contactPerson = '';

    #[ORM\Column(name: 'street_address', type: 'string', length: 100)]
    /** @var string */
    protected string $streetAddress = '';

    #[ORM\Column(name: 'zip_code', type: 'string', length: 10)]
    /** @var string */
    protected string $zipCode = '';

    #[ORM\Column(type: 'string', length: 50)]
    /** @var string */
    protected string $city = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $country = null;

    #[ORM\Column(type: 'string', length: 50)]
    /** @var string */
    protected string $phone = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $www = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    /** @var ?string */
    protected ?string $email = null;

    #[ORM\Column(name: 'company_id', type: 'string', length: 15, nullable: true)]
    /** @var ?string */
    protected ?string $companyId = null;

    #[ORM\Column(name: 'org_unit_number', type: 'string', length: 35, nullable: true)]
    /** @var ?string */
    protected ?string $orgUnitNumber = null;

    #[ORM\Column(name: 'payment_intermediator', type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $paymentIntermediator = null;

    #[ORM\Column(name: 'payment_recipient_name', type: 'string', length: 100, nullable: true)]
    /** @var ?string */
    protected ?string $paymentRecipientName = null;

    #[ORM\Column(name: 'bank_name', type: 'string', length: 50)]
    /** @var string */
    protected string $bankName = '';

    #[ORM\Column(name: 'bank_account', type: 'string', length: 30)]
    /** @var string */
    protected string $bankAccount = '';

    #[ORM\Column(name: 'bank_iban', type: 'string', length: 50)]
    /** @var string */
    protected string $bankIban = '';

    #[ORM\Column(name: 'bank_swiftbic', type: 'string', length: 30)]
    /** @var string */
    protected string $bankSwiftbic = '';

    #[ORM\Column(name: 'bank_name2', type: 'string', length: 50, nullable: true)]
    /** @var ?string */
    protected ?string $bankName2 = null;

    #[ORM\Column(name: 'bank_account2', type: 'string', length: 30, nullable: true)]
    /** @var ?string */
    protected ?string $bankAccount2 = null;

    #[ORM\Column(name: 'bank_iban2', type: 'string', length: 50, nullable: true)]
    /** @var ?string */
    protected ?string $bankIban2 = null;

    #[ORM\Column(name: 'bank_swiftbic2', type: 'string', length: 30, nullable: true)]
    /** @var ?string */
    protected ?string $bankSwiftbic2 = null;

    #[ORM\Column(name: 'bank_name3', type: 'string', length: 50, nullable: true)]
    /** @var ?string */
    protected ?string $bankName3 = null;

    #[ORM\Column(name: 'bank_account3', type: 'string', length: 30, nullable: true)]
    /** @var ?string */
    protected ?string $bankAccount3 = null;

    #[ORM\Column(name: 'bank_iban3', type: 'string', length: 50, nullable: true)]
    /** @var ?string */
    protected ?string $bankIban3 = null;

    #[ORM\Column(name: 'bank_swiftbic3', type: 'string', length: 30, nullable: true)]
    /** @var ?string */
    protected ?string $bankSwiftbic3 = null;

    #[ORM\Column(name: 'vat_registered', type: 'boolean')]
    /** @var bool */
    protected bool $vatRegistered = false;

    #[ORM\Column(name: 'logo_filename', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $logoFilename = null;

    #[ORM\Column(name: 'logo_filesize', type: 'integer', nullable: true)]
    /** @var int|null */
    protected ?int $logoFilesize = null;

    #[ORM\Column(name: 'logo_filetype', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $logoFiletype = null;

    #[ORM\Column(name: 'logo_filedata', type: 'blob', nullable: true)]
    /** @var resource|string|null */
    protected $logoFiledata = null;

    #[ORM\Column(name: 'logo_top', type: 'decimal', precision: 9, scale: 2, nullable: true)]
    /** @var ?string */
    protected ?string $logoTop = null;

    #[ORM\Column(name: 'logo_left', type: 'decimal', precision: 9, scale: 2, nullable: true)]
    /** @var ?string */
    protected ?string $logoLeft = null;

    #[ORM\Column(name: 'logo_width', type: 'decimal', precision: 9, scale: 2, nullable: true)]
    /** @var ?string */
    protected ?string $logoWidth = null;

    #[ORM\Column(name: 'logo_bottom_margin', type: 'decimal', precision: 9, scale: 2, nullable: true)]
    /** @var ?string */
    protected ?string $logoBottomMargin = null;

    #[ORM\Column(name: 'invoice_email_from', type: 'string', length: 512, nullable: true)]
    /** @var ?string */
    protected ?string $invoiceEmailFrom = null;

    #[ORM\Column(name: 'invoice_email_bcc', type: 'string', length: 512, nullable: true)]
    /** @var ?string */
    protected ?string $invoiceEmailBcc = null;

    #[ORM\Column(name: 'invoice_email_subject', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $invoiceEmailSubject = null;

    #[ORM\Column(name: 'invoice_email_body', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $invoiceEmailBody = null;

    #[ORM\Column(name: 'receipt_email_subject', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $receiptEmailSubject = null;

    #[ORM\Column(name: 'receipt_email_body', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $receiptEmailBody = null;

    #[ORM\Column(name: 'order_confirmation_email_subject', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $orderConfirmationEmailSubject = null;

    #[ORM\Column(name: 'order_confirmation_email_body', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $orderConfirmationEmailBody = null;

    #[ORM\Column(name: 'offer_email_subject', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $offerEmailSubject = null;

    #[ORM\Column(name: 'offer_email_body', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $offerEmailBody = null;

    #[ORM\Column(name: 'invoice_default_info', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $invoiceDefaultInfo = null;

    #[ORM\Column(name: 'invoice_default_foreword', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $invoiceDefaultForeword = null;

    #[ORM\Column(name: 'invoice_default_afterword', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $invoiceDefaultAfterword = null;

    #[ORM\Column(name: 'offer_default_foreword', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $offerDefaultForeword = null;

    #[ORM\Column(name: 'offer_default_afterword', type: 'text', nullable: true)]
    /** @var ?string */
    protected ?string $offerDefaultAfterword = null;

    #[ORM\Column(name: 'terms_of_payment', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $termsOfPayment = null;

    #[ORM\Column(name: 'period_for_complaints', type: 'string', length: 255, nullable: true)]
    /** @var ?string */
    protected ?string $periodForComplaints = null;

    #[ORM\OneToMany(mappedBy: 'base', targetEntity: SendApiConfig::class, cascade: ['persist','remove'])]
    /** @var Collection<int, SendApiConfig> */
    protected Collection $sendApiConfigs;

    /**
     * Get id.
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): static
    {
        $this->name = $name; return $this;
    }
    public function isDeleted(): bool
    {
        return $this->deleted;
    }
    public function setDeleted(bool $d): static
    {
        $this->deleted = $d; return $this;
    }
    public function isInactive(): bool
    {
        return $this->inactive;
    }
    public function setInactive(bool $v): static
    {
        $this->inactive = $v; return $this;
    }
    public function getContactPerson(): string
    {
        return $this->contactPerson;
    }
    public function setContactPerson(string $p): static
    {
        $this->contactPerson = $p; return $this;
    }
    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }
    public function setStreetAddress(string $s): static
    {
        $this->streetAddress = $s; return $this;
    }
    public function getZipCode(): string
    {
        return $this->zipCode;
    }
    public function setZipCode(string $z): static
    {
        $this->zipCode = $z; return $this;
    }
    public function getCity(): string
    {
        return $this->city;
    }
    public function setCity(string $c): static
    {
        $this->city = $c; return $this;
    }
    public function getCountry(): ?string
    {
        return $this->country;
    }
    public function setCountry(?string $c): static
    {
        $this->country = $c; return $this;
    }
    public function getPhone(): string
    {
        return $this->phone;
    }
    public function setPhone(string $p): static
    {
        $this->phone = $p; return $this;
    }
    public function getWww(): ?string
    {
        return $this->www;
    }
    public function setWww(?string $w): static
    {
        $this->www = $w; return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(?string $e): static
    {
        $this->email = $e; return $this;
    }
    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }
    public function setCompanyId(?string $c): static
    {
        $this->companyId = $c; return $this;
    }
    public function getOrgUnitNumber(): ?string
    {
        return $this->orgUnitNumber;
    }
    public function setOrgUnitNumber(?string $v): static
    {
        $this->orgUnitNumber = $v; return $this;
    }
    public function getPaymentIntermediator(): ?string
    {
        return $this->paymentIntermediator;
    }
    public function setPaymentIntermediator(?string $v): static
    {
        $this->paymentIntermediator = $v; return $this;
    }
    public function getPaymentRecipientName(): ?string
    {
        return $this->paymentRecipientName;
    }
    public function setPaymentRecipientName(?string $v): static
    {
        $this->paymentRecipientName = $v; return $this;
    }
    public function getBankName(): string
    {
        return $this->bankName;
    }
    public function setBankName(string $v): static
    {
        $this->bankName = $v; return $this;
    }
    public function getBankAccount(): string
    {
        return $this->bankAccount;
    }
    public function setBankAccount(string $a): static
    {
        $this->bankAccount = $a; return $this;
    }
    public function getBankIban(): string
    {
        return $this->bankIban;
    }
    public function setBankIban(string $v): static
    {
        $this->bankIban = $v; return $this;
    }
    public function getBankSwiftbic(): string
    {
        return $this->bankSwiftbic;
    }
    public function setBankSwiftbic(string $v): static
    {
        $this->bankSwiftbic = $v; return $this;
    }
    public function getBankName2(): ?string
    {
        return $this->bankName2;
    }
    public function setBankName2(?string $v): static
    {
        $this->bankName2 = $v; return $this;
    }
    public function getBankAccount2(): ?string
    {
        return $this->bankAccount2;
    }
    public function setBankAccount2(?string $v): static
    {
        $this->bankAccount2 = $v; return $this;
    }
    public function getBankIban2(): ?string
    {
        return $this->bankIban2;
    }
    public function setBankIban2(?string $v): static
    {
        $this->bankIban2 = $v; return $this;
    }
    public function getBankSwiftbic2(): ?string
    {
        return $this->bankSwiftbic2;
    }
    public function setBankSwiftbic2(?string $v): static
    {
        $this->bankSwiftbic2 = $v; return $this;
    }
    public function getBankName3(): ?string
    {
        return $this->bankName3;
    }
    public function setBankName3(?string $v): static
    {
        $this->bankName3 = $v; return $this;
    }
    public function getBankAccount3(): ?string
    {
        return $this->bankAccount3;
    }
    public function setBankAccount3(?string $v): static
    {
        $this->bankAccount3 = $v; return $this;
    }
    public function getBankIban3(): ?string
    {
        return $this->bankIban3;
    }
    public function setBankIban3(?string $v): static
    {
        $this->bankIban3 = $v; return $this;
    }
    public function getBankSwiftbic3(): ?string
    {
        return $this->bankSwiftbic3;
    }
    public function setBankSwiftbic3(?string $v): static
    {
        $this->bankSwiftbic3 = $v; return $this;
    }
    public function isVatRegistered(): bool
    {
        return $this->vatRegistered;
    }
    public function setVatRegistered(bool $v): static
    {
        $this->vatRegistered = $v; return $this;
    }
    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }
    public function setLogoFilename(?string $v): static
    {
        $this->logoFilename = $v; return $this;
    }
    public function getLogoFilesize(): ?int
    {
        return $this->logoFilesize;
    }
    public function setLogoFilesize(?int $v): static
    {
        $this->logoFilesize = $v; return $this;
    }
    public function getLogoFiletype(): ?string
    {
        return $this->logoFiletype;
    }
    public function setLogoFiletype(?string $v): static
    {
        $this->logoFiletype = $v; return $this;
    }
    public function getLogoFiledata()
    {
        return $this->logoFiledata;
    }
    public function setLogoFiledata($v): static
    {
        $this->logoFiledata = $v; return $this;
    }
    public function getLogoTop(): ?string
    {
        return $this->logoTop;
    }
    public function setLogoTop(?string $v): static
    {
        $this->logoTop = $v; return $this;
    }
    public function getLogoLeft(): ?string
    {
        return $this->logoLeft;
    }
    public function setLogoLeft(?string $v): static
    {
        $this->logoLeft = $v; return $this;
    }
    public function getLogoWidth(): ?string
    {
        return $this->logoWidth;
    }
    public function setLogoWidth(?string $v): static
    {
        $this->logoWidth = $v; return $this;
    }
    public function getLogoBottomMargin(): ?string
    {
        return $this->logoBottomMargin;
    }
    public function setLogoBottomMargin(?string $v): static
    {
        $this->logoBottomMargin = $v; return $this;
    }
    public function getInvoiceEmailFrom(): ?string
    {
        return $this->invoiceEmailFrom;
    }
    public function setInvoiceEmailFrom(?string $v): static
    {
        $this->invoiceEmailFrom = $v; return $this;
    }
    public function getInvoiceEmailBcc(): ?string
    {
        return $this->invoiceEmailBcc;
    }
    public function setInvoiceEmailBcc(?string $v): static
    {
        $this->invoiceEmailBcc = $v; return $this;
    }
    public function getInvoiceEmailSubject(): ?string
    {
        return $this->invoiceEmailSubject;
    }
    public function setInvoiceEmailSubject(?string $v): static
    {
        $this->invoiceEmailSubject = $v; return $this;
    }
    public function getInvoiceEmailBody(): ?string
    {
        return $this->invoiceEmailBody;
    }
    public function setInvoiceEmailBody(?string $v): static
    {
        $this->invoiceEmailBody = $v; return $this;
    }
    public function getReceiptEmailSubject(): ?string
    {
        return $this->receiptEmailSubject;
    }
    public function setReceiptEmailSubject(?string $v): static
    {
        $this->receiptEmailSubject = $v; return $this;
    }
    public function getReceiptEmailBody(): ?string
    {
        return $this->receiptEmailBody;
    }
    public function setReceiptEmailBody(?string $v): static
    {
        $this->receiptEmailBody = $v; return $this;
    }
    public function getOrderConfirmationEmailSubject(): ?string
    {
        return $this->orderConfirmationEmailSubject;
    }
    public function setOrderConfirmationEmailSubject(?string $v): static
    {
        $this->orderConfirmationEmailSubject = $v; return $this;
    }
    public function getOrderConfirmationEmailBody(): ?string
    {
        return $this->orderConfirmationEmailBody;
    }
    public function setOrderConfirmationEmailBody(?string $v): static
    {
        $this->orderConfirmationEmailBody = $v; return $this;
    }
    public function getOfferEmailSubject(): ?string
    {
        return $this->offerEmailSubject;
    }
    public function setOfferEmailSubject(?string $v): static
    {
        $this->offerEmailSubject = $v; return $this;
    }
    public function getOfferEmailBody(): ?string
    {
        return $this->offerEmailBody;
    }
    public function setOfferEmailBody(?string $v): static
    {
        $this->offerEmailBody = $v; return $this;
    }
    public function getInvoiceDefaultInfo(): ?string
    {
        return $this->invoiceDefaultInfo;
    }
    public function setInvoiceDefaultInfo(?string $v): static
    {
        $this->invoiceDefaultInfo = $v; return $this;
    }
    public function getInvoiceDefaultForeword(): ?string
    {
        return $this->invoiceDefaultForeword;
    }
    public function setInvoiceDefaultForeword(?string $v): static
    {
        $this->invoiceDefaultForeword = $v; return $this;
    }
    public function getInvoiceDefaultAfterword(): ?string
    {
        return $this->invoiceDefaultAfterword;
    }
    public function setInvoiceDefaultAfterword(?string $v): static
    {
        $this->invoiceDefaultAfterword = $v; return $this;
    }
    public function getOfferDefaultForeword(): ?string
    {
        return $this->offerDefaultForeword;
    }
    public function setOfferDefaultForeword(?string $v): static
    {
        $this->offerDefaultForeword = $v; return $this;
    }
    public function getOfferDefaultAfterword(): ?string
    {
        return $this->offerDefaultAfterword;
    }
    public function setOfferDefaultAfterword(?string $v): static
    {
        $this->offerDefaultAfterword = $v; return $this;
    }
    public function getTermsOfPayment(): ?string
    {
        return $this->termsOfPayment;
    }
    public function setTermsOfPayment(?string $v): static
    {
        $this->termsOfPayment = $v; return $this;
    }
    public function getPeriodForComplaints(): ?string
    {
        return $this->periodForComplaints;
    }
    public function setPeriodForComplaints(?string $v): static
    {
        $this->periodForComplaints = $v; return $this;
    }

    /**
     *
     *
     * @return Collection<int, SendApiConfig>
     */
    public function getSendApiConfigs(): Collection
    {
        return $this->sendApiConfigs;
    }

    /**
     *
     *
     * @param SendApiConfig $s
     *
     * @return static
     */
    public function addSendApiConfig(SendApiConfig $s): self { if (! $this->sendApiConfigs->contains($s))
    {
        $this->sendApiConfigs->add($s); $s->setBase($this); } return $this;
    }

    /**
     *
     *
     * @param SendApiConfig $s
     *
     * @return static
     */
    public function removeSendApiConfig(SendApiConfig $s): self { if ($this->sendApiConfigs->removeElement($s))
    {
        $s->setBase(null); } return $this;
    }

    public function __construct()
    {
        $this->sendApiConfigs = new ArrayCollection();
    }
}
