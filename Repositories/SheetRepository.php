<?php

namespace BigSheetImporter\Repositories;

use BigSheetImporter\Entities\Sheet;
use Doctrine\Common\Collections\ArrayCollection;
use MapasCulturais\Repository;

class SheetRepository extends Repository
{
    /**
     * @return array<Sheet>
     */
    public function findHistory(int $limit = 50, int $page = 1): array
    {
        $offset = $limit * ($page - 1);

        return $this->createQueryBuilder('s')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
