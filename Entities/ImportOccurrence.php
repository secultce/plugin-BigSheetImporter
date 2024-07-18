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
     * @ORM\GeneratedValue(strategy="identity")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="timestamp", nullable=false)
     */
    protected $date;

    /**
     * @var int
     *
     * @ORM\OneToMany(targetEntity="Sheet")
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
     * @var int
     *
     * @ORM\Column(name="column_index", type="integer")
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
}
