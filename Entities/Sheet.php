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
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="rows_amount", type="integer")
     */
    protected $rowsAmount;

    /**
     * @var RowSheet[]
     *
     * @ORM\OneToMany(targetEntity=\BigSheetImporter\Entities\RowSheet::class, mappedBy="sheet", cascade="persist")
     */
    protected $rows;

    /**
     * @var ImportOccurrence[]
     *
     * @ORM\OneToMany(targetEntity=\BigSheetImporter\Entities\ImportOccurrence::class, mappedBy="sheet", cascade="persist")
     */
    protected $occurrences;

    /** @override  */
    public function jsonSerialize()
    {
        $serialized = parent::jsonSerialize();
        $userId = $serialized['user']->id;
        $userName = $serialized['user']->profile->name;

        $serialized['user'] = (object)compact('userId', 'userName');

        return $serialized;
    }
}
