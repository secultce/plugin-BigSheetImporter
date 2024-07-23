<?php

namespace BigSheetImporter\Entities;

use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\Entity;

/**
 * @ORM\Table(name="import_occurrence")
 * @ORM\Entity(repositoryClass="MapasCulturais\Repository")
 */
class ImportOccurrence extends Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    protected $date;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="Sheet")
     * @ORM\JoinColumn(name="sheet_id", referencedColumnName="id")
     */
    protected $sheet;

    /**
     * @var int
     *
     * @ORM\Column(name="row_index", type="integer")
     */
    protected $rowIndex;

    /**
     * @var string
     *
     * @ORM\Column(name="column_index", type="string")
     */
    protected $columnIndex;

    /**
     * @var string
     *
     * @ORM\Column(name="occurence", type="string")
     */
    protected $occurence;

    /**
     * @var string
     *
     * @ORM\Column(name="given_value", type="string")
     */
    protected $givenValue;

    public function __construct(int $rowIndex, string $columnIndex, string $occurence, string $givenValue, ?Sheet $sheet = null)
    {
        parent::__construct();

        $this->sheet = $sheet;
        $this->rowIndex = $rowIndex;
        $this->columnIndex = $columnIndex;
        $this->occurence = $occurence;
        $this->givenValue = $givenValue;
        $this->date = new \DateTime();
    }
}
