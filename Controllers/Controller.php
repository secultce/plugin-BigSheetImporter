<?php

namespace BigSheetImporter\Controllers;

use BigSheetImporter\Exceptions\InvalidSheetFormat;
use BigSheetImporter\Services\SheetService;
use BigSheetImporter\Entities\Sheet;
use MapasCulturais\i;
use Shuchkin\{SimpleXLSX, SimpleXLS, SimpleXLSXGen};

class Controller extends \MapasCulturais\Controller
{
    public function POST_import(): void
    {
        $this->requireAuthentication();
        $tmpFilename = $_FILES['spreadsheet']['tmp_name'];

        $xlsData = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

        $app = \MapasCulturais\App::getInstance();
        if (!$app->user->isUserAdmin($this->app->user)) {
            $this->json('', 403);
            return;
        }

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

    public function GET_templateSheet(): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="template.xlsx"');
        SimpleXLSXGen::fromArray([[
            i::__('CÓDIGO DA INSCRIÇÃO'),
            i::__('NÚMERO DO PROCESSO'),
            i::__('NÚMERO DO SACC'),
            i::__('NÚMERO DO TERMO'),
            i::__('NÚMERO DO EMPENHO'),
            i::__('VALOR DE REPASSE'),
            i::__('DATA DE ABERTURA DO PROCESSO'),
            i::__('DATA DE ENVIO DO COMUNICADO AO PROPONENTE'),
            i::__('DATA DE RECEBIMENTO ASJUR'),
            i::__('DATA DO ENVIO DO TERMO DE FOMENTO PARA ASSINATURA DO PROPONENTE'),
            i::__('DATA DE ENVIO PARA CASA CIVIL'),
            i::__('DATA DE PUBLICAÇÃO NO DOE'),
            i::__('DATA DE SOLICITAÇÃO DA PARCELA'),
            i::__('DATA DE CONFERÊNCIA E-PARCERIAS'),
            i::__('DATA DO EMPENHO'),
            i::__('DATA DO PAGAMENTO'),
            i::__('DATA DE INÍCIO DA VIGÊNCIA DO TERMO ASSINADO'),
            i::__('DATA DO TERMINO DA VIGÊNCIA DO TERMO ASSINADO'),
            i::__('NOME DO FISCAL'),
            i::__('CPF DO FISCAL'),
            i::__('MATRÍCULA DO FISCAL'),
        ]], 'Modelo de Planilha')->download();
        exit();
    }
}
