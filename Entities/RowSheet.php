<?php

namespace BigSheetImporter\Entities;

use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\App;
use MapasCulturais\Entity;

/**
 * @ORM\Table(name="row_sheet_import")
 * @ORM\Entity(repositoryClass=\BigSheetImporter\Repositories\RowSheetRepository::class)
 */
class RowSheet extends Entity
{
    const RAIO_NOTIFICATIONS_STATUS = 1;
    const REFO_NOTIFICATIONS_STATUS = 2;
    const ALL_NOTIFICATIONS_SENT_STATUS = 3;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Sheet
     *
     * @ORM\ManyToOne(targetEntity="Sheet", inversedBy="rows")
     * @ORM\JoinColumn(name="sheet_id", referencedColumnName="id")
     */
    protected $sheet;

    public $registration;

    /**
     * @ORM\Column(name="registration_number", type="string", length=20, unique=true)
     */
    protected $registrationNumber;

    /**
     * @ORM\Column(name="process_number", type="string", length=20, unique=true)
     */
    protected $processNumber;

    /**
     * @ORM\Column(name="sacc_number", type="integer", nullable=true)
     */
    protected $saccNumber;

    /**
     * @ORM\Column(name="term_number", type="integer", nullable=true)
     */
    protected $termNumber;

    /**
     * @ORM\Column(name="interest_number", type="string", length=12, nullable=true)
     */
    protected $interestNumber;

    /**
     * @ORM\Column(name="trasfer_value", type="decimal", scale=2, nullable=true)
     */
    protected $trasferValue;

    /**
     * @ORM\Column(name="process_date", type="datetime", nullable=true)
     */
    protected $processDate;

    /**
     * @ORM\Column(name="communication_to_proponent_sent_date", type="datetime", nullable=true)
     */
    protected $communicationToProponentSentDate;

    /**
     * @ORM\Column(name="asjur_receipt_date", type="datetime", nullable=true)
     */
    protected $asjurReceiptDate;

    /**
     * @ORM\Column(name="proponent_signature_terms_sent_date", type="datetime", nullable=true)
     */
    protected $proponentSignatureTermsSentDate;

    /**
     * @ORM\Column(name="casa_civil_sent_date", type="datetime", nullable=true)
     */
    protected $casaCivilSentDate;

    /**
     * @ORM\Column(name="doe_publish_date", type="datetime", nullable=true)
     */
    protected $doePublishDate;

    /**
     * @ORM\Column(name="installment_request_date", type="datetime", nullable=true)
     */
    protected $installmentRequestDate;

    /**
     * @ORM\Column(name="eparcerias_conference_date",type="datetime", nullable=true)
     */
    protected $eparceriasConferenceDate;

    /**
     * @ORM\Column(name="interest_date", type="datetime", nullable=true)
     */
    protected $interestDate;

    /**
     * @ORM\Column(name="payment_date", type="datetime", nullable=true)
     */
    protected $paymentDate;

    /**
     * @ORM\Column(name="signed_term_validity_init_date", type="datetime", nullable=true)
     */
    protected $signedTermValidityInitDate;

    /**
     * @ORM\Column(name="signed_term_validity_end_date", type="datetime", nullable=true)
     */
    protected $signedTermValidityEndDate;

    /**
     * @var string
     *
     * @ORM\Column(name="fiscal_cpf", type="string", length=14, nullable=false)
     */
    protected $fiscalCpf;

    /**
     * @var string
     *
     * @ORM\Column(name="fiscal_name", type="text", nullable=false)
     */
    protected $fiscalName;

    /**
     * @var string
     *
     * @ORM\Column(name="fiscal_registry", type="string", length=20, nullable=false)
     */
    protected $fiscalRegistry;

    /**
     * @var integer
     *
     * @ORM\Column(name="notification_status", type="smallint", nullable=false)
     */
    protected $notificationStatus = self::RAIO_NOTIFICATIONS_STATUS;

    public function setRowSheet(
        ?string $processNumber = null,
        ?int $saccNumber = null,
        ?int $termNumber = null,
        ?string $interestNumber = null,
        ?float $trasferValue = null,
        ?\DateTime $processDate = null,
        ?\DateTime $communicationToProponentSentDate = null,
        ?\DateTime $asjurReceiptDate = null,
        ?\DateTime $proponentSignatureTermsSentDate = null,
        ?\DateTime $casaCivilSentDate = null,
        ?\DateTime $doePublishDate = null,
        ?\DateTime $installmentRequestDate = null,
        ?\DateTime $eparceriasConferenceDate = null,
        ?\DateTime $interestDate = null,
        ?\DateTime $paymentDate = null,
        ?\DateTime $signedTermValidityInitDate = null,
        ?\DateTime $signedTermValidityEndDate = null,
        ?string $fiscalName = null,
        ?string $fiscalCpf = null,
        ?string $fiscalRegistry = null
    ): void {
        $this->processNumber = $processNumber;
        $this->saccNumber = $saccNumber;
        $this->termNumber = $termNumber;
        $this->interestNumber = $interestNumber;
        $this->trasferValue = $trasferValue;
        $this->processDate = $processDate;
        $this->communicationToProponentSentDate = $communicationToProponentSentDate;
        $this->asjurReceiptDate = $asjurReceiptDate;
        $this->proponentSignatureTermsSentDate = $proponentSignatureTermsSentDate;
        $this->casaCivilSentDate = $casaCivilSentDate;
        $this->doePublishDate = $doePublishDate;
        $this->installmentRequestDate = $installmentRequestDate;
        $this->eparceriasConferenceDate = $eparceriasConferenceDate;
        $this->interestDate = $interestDate;
        $this->paymentDate = $paymentDate;
        $this->signedTermValidityInitDate = $signedTermValidityInitDate;
        $this->signedTermValidityEndDate = $signedTermValidityEndDate;
        $this->fiscalCpf = $fiscalCpf;
        $this->fiscalName = $fiscalName;
        $this->fiscalRegistry = $fiscalRegistry;
    }

    /** @override */
    public function jsonSerialize()
    {
        $serializedArray = parent::jsonSerialize();

        unset($serializedArray['registration']);
        unset($serializedArray['sheet']);

        return $serializedArray;
    }

    public function updateNotificationStatus()
    {
        $app = App::i();

        $app->disableAccessControl();

        switch ($this->notificationStatus) {
            case self::REFO_NOTIFICATIONS_STATUS:
                $this->notificationStatus = self::ALL_NOTIFICATIONS_SENT_STATUS;
                break;
            default:
                $this->notificationStatus = self::REFO_NOTIFICATIONS_STATUS;
        }

        $this->save(true);
        $app->enableAccessControl();
    }
}
