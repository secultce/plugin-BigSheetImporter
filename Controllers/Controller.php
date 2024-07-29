<?php

namespace BigSheetImporter\Controllers;

use BigSheetImporter\Exceptions\InvalidSheetFormat;
use BigSheetImporter\Services\SheetService;
use BigSheetImporter\Entities\Sheet;
use Shuchkin\{SimpleXLSX, SimpleXLS};

class Controller extends \MapasCulturais\Controller
{
    public function POST_import(): void
    {
        $this->requireAuthentication();
        $tmpFilename = $_FILES['spreadsheet']['tmp_name'];

        $xlsData = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

        $app = \MapasCulturais\App::getInstance();

        $app->em->beginTransaction();
        try {
            $app->disableAccessControl();

            $sheet = new Sheet();
            $sheet->date = new \DateTime();
            $sheet->user = $app->user;
            $sheet->rowsAmount = count($xlsData->rows()) - 1;
            $sheet->save(true);

            $validate = SheetService::validate($xlsData);
            $sheet->occurrences = SheetService::createOccurrences($validate->invalidData, $sheet);
            $sheet->save(true);

            $sheet->rows = SheetService::createRows($xlsData->rows(), $sheet, $validate->invalidRows);
            $sheet->rowsSaved = count($sheet->rows);
            $sheet->save(true);

            $app->em->commit();
        } catch (InvalidSheetFormat $e) {
            $app->em->rollback();
            $this->json(['error' => $e->getMessage()], 400);
            return;
        } catch (\Exception $e) {
            $app->em->rollback();
            $this->json(['error' => $e->getMessage()], 500);
            return;
        } finally {
            $app->enableAccessControl();
        }

        $data = [
            'sheet' => $sheet,
            'rowsSaved' => $sheet->rows->toArray(),
            'occurrences' => $sheet->occurrences->toArray(),
        ];

        $this->json($data, 201);
    }
}
