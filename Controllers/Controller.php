<?php

namespace BigSheetImporter\Controllers;

use BigSheetImporter\Exceptions\InvalidSheetFormat;
use BigSheetImporter\Services\SheetService;
use MapasCulturais\Exceptions\PermissionDenied;
use MapasCulturais\Exceptions\WorkflowRequest;
use BigSheetImporter\Entities\{RowSheet, Sheet};
use MapasCulturais\Entities\Registration;
use Shuchkin\{SimpleXLSX, SimpleXLS};

class Controller extends \MapasCulturais\Controller
{
    /**
     * @throws WorkflowRequest
     * @throws PermissionDenied
     */
    public function POST_import(): void
    {
//        $this->requireAuthentication();
        $tmpFilename = $_FILES['first']['tmp_name'];

        $xlsData = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

        $app = \MapasCulturais\App::getInstance();

        $app->em->beginTransaction();
        try {
            $app->disableAccessControl();
            $sheet = new Sheet();
            $sheet->date = new \DateTime();
            $sheet->user = $app->user;
            $sheet->rowsAmount = count($xlsData->rows());
            $sheet->save(true);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }

        try {
            $validate = SheetService::validate($xlsData);
            $sheet->occurrences = SheetService::createOccurrences($validate->invalidData, $sheet);
            $sheet->save(true);
        } catch (InvalidSheetFormat $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $app->em->rollback();
            $this->json(['error' => $e->getMessage()], 500);
        }

        try {
            $sheet->rows = SheetService::createRows($xlsData->rows(), $sheet, $validate->invalidRows);
            $sheet->rowsSaved = count($sheet->rows);
            $sheet->save(true);

            $app->em->commit();
        } catch (\Exception $e) {
            $app->em->rollback();
            $this->json(['error' => $e->getMessage()], 500);
        }
        $app->enableAccessControl();
    }

    public function GET_sheet(): void
    {
        $app = \MapasCulturais\App::getInstance();
        $sheetId = $this->data['id'];

        $test = new RowSheet();

        $sheet = $app->repo(Sheet::class)->find($sheetId);
        foreach ($sheet->rows as $row ) {
            dump($row);
        }

        $a = $app->repo(RowSheet::class)->findAll();
        $reg = $app->repo(Registration::class)->findBy(['number' => $a[0]->registrationNumber]);
        dump($a);
        dump($reg);

    }
}
