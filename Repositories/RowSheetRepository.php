<?php

namespace BigSheetImporter\Repositories;

use BigSheetImporter\Entities\RowSheet;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Repository;

class RowSheetRepository extends Repository
{
    /** @override  */
    public function findOneBy(array $criteria, ?array $orderBy = null): RowSheet
    {
        $rowSheetObject = parent::findOneBy($criteria, $orderBy);
        $rowSheetObject->registration = $this->_em->getRepository(Registration::class)
            ->findBy(['number' => $rowSheetObject->registrationNumber]);

        return $rowSheetObject;
    }

    /** @override  */
    public function find($id, $lockMode = null, $lockVersion = null): RowSheet
    {
        /** @var RowSheet $rowSheetObject */
        $rowSheetObject = parent::find($id, $lockMode, $lockVersion);
        $rowSheetObject->registration = $this->_em->getRepository(Registration::class)
            ->findBy(['number' => $rowSheetObject->registrationNumber]);

        return $rowSheetObject;
    }

    /** @override  */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        $rowSheetCollection = parent::findBy($criteria, $orderBy, $limit, $offset);
        foreach ($rowSheetCollection as $rowSheetObject) {
            $rowSheetObject->registration = $this->_em->getRepository(Registration::class)
                ->findBy(['number' => $rowSheetObject->registrationNumber]);
        }
        return $rowSheetCollection;
    }
}
