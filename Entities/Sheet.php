<?php

namespace BigSheetImporter\Entities;

use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\Entity;

/**
 * @ORM\Table(name="sheet_import")
 * @ORM\Entity(repositoryClass="MapasCulturais\Repository")
 */
class Sheet extends Entity
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
     * @ORM\Column(name="date", type="datetime")
     */
    protected $date;

    /**
     * @var \MapasCulturais\Entities\User
     *
     * @ORM\OneToMany(targetEntity="MapasCulturais\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="MapasCulturais\Entities\Opportunity", indexBy="opportunity")
     * @ORM\JoinColumn(name="opportunity_id", referencedColumnName="id", nullable=false, onDelete="cascade")
     */
    protected $opportunity;

    /**
     * @var int
     *
     * @ORM\Column(name="rows_amount", type="integer")
     */
    protected $rowsAmount;

    /**
     * @var object[]
     *
     * @ORM\JoinTable(name="row_sheet_import",
     *     joinColumns={@ORM\JoinColumn(name="sheet_id", referencedColumnName="id")}
     * )
     */
    protected $rows;
}
