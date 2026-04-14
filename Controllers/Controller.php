<?php

namespace BigSheetImporter\Controllers;

use BigSheetImporter\Entities\RowSheet;
use BigSheetImporter\Exceptions\InvalidSheetFormat;
use BigSheetImporter\Services\SheetService;
use BigSheetImporter\Entities\Sheet;
use Carbon\Carbon;
use Diligence\Entities\Diligence;
use MapasCulturais\App;
use MapasCulturais\i;
use Shuchkin\{SimpleXLSX, SimpleXLS, SimpleXLSXGen};
use MapasCulturais\Services\SentryService;

class Controller extends \MapasCulturais\Controller
{
    private $infosForNotifications = [];
    private $rowSheet;

    public function POST_import(): void
    {
        $this->requireAuthentication();
        $tmpFilename = $_FILES['spreadsheet']['tmp_name'];

        $xlsData = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

        if (!$xlsData) {
            $this->json(['error' => i::__('Não foi possível ler a planilha. Verifique se o arquivo é um .xlsx ou .xls válido.')], 400);
            return;
        }

        $app = App::getInstance();
        if (!$app->user->isUserAdmin($app->user)) {
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
        } catch (\Throwable $e) {
            $app->em->rollback();
            SentryService::captureExceptions($e);
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
            i::__('INSTRUMENTO'),
            i::__('MUNICIPIO'),
        ]], 'Modelo de Planilha')->download();
        exit();
    }

    public function GET_infoForNotificationsAccountability()
    {

        if (!isset($this->data['access_token']) || $this->data['access_token'] !== $_ENV['ACCESS_TOKEN_API_EMAIL']) {
            $this->json(['message' => 'Acesso não autorizado'], 401);
        }

        $this->setInfoRaioNotifications();
        $this->setInfoRefoNotifications();

        $this->json(array_values($this->infosForNotifications));
    }

    private function setInfoRaioNotifications()
    {
        $app = App::i();
        $rowSheets = $app->repo(RowSheet::class)->findBy(['notificationStatus' => RowSheet::RAIO_NOTIFICATIONS_STATUS]);
        $terms = $app->repo('Term')->findBy([
            'taxonomy' => 'notifications_accountability',
            'description' => 'raio'
        ]);
        $accountabilityDeadline = $app->repo('Term')->findOneBy([
            'taxonomy' => 'accountability_deadline',
            'description' => 'raio'
        ]);
        foreach ($rowSheets as $rowSheet) {
            $diffInDays = Carbon::parse($rowSheet->signedTermValidityInitDate)->diffInDays(Carbon::now());
            $this->checkNotificationDay($terms, $diffInDays, $rowSheet, $accountabilityDeadline, 'raio');
        }
    }

    private function setInfoRefoNotifications()
    {
        $app = App::i();
        $rowSheets = $app->repo(RowSheet::class)->findBy(['notificationStatus' => RowSheet::REFO_NOTIFICATIONS_STATUS]);
        $terms = $app->repo('Term')->findBy([
            'taxonomy' => 'notifications_accountability',
            'description' => 'refo'
        ]);
        $accountabilityDeadline = $app->repo('Term')->findOneBy([
            'taxonomy' => 'accountability_deadline',
            'description' => 'refo'
        ]);

        foreach ($rowSheets as $rowSheet) {
            $diffInDays = Carbon::parse($rowSheet->signedTermValidityEndDate)->diffInDays(Carbon::now());

            $this->checkNotificationDay($terms, $diffInDays, $rowSheet, $accountabilityDeadline, 'refo');
        }
    }

    /**
     * Chega os dias das últimas notificações para não enviar em duplicidade
     * @param mixed $terms
     * @param mixed $days
     * @param mixed $rowSheet
     * @param mixed $accountabilityDeadline
     * @param mixed $notificationType
     * @return void
     */
    private function checkNotificationDay($terms, $days, $rowSheet, $accountabilityDeadline, $notificationType)
    {
        $hasTerm = array_filter($terms, function ($term) use ($days) {
            return (int)$term->term === $days;
        });

        if ($hasTerm) {
            $isLastNotification = false;
            if ($days < (int)$accountabilityDeadline->term) {
                $diffDays = (int)$accountabilityDeadline->term - $days;
                $futureDay = Carbon::now()->addDays($diffDays)->format('d/m/Y');
                $notificationMsg = "encerra-se no dia $futureDay";
            } elseif ($days === (int)$accountabilityDeadline->term) {
                $todayDate = Carbon::now()->format('d/m/Y');
                $notificationMsg = "encerra-se hoje $todayDate";
                // Última notificação para o refo
                if ($notificationType === 'refo') {
                    $isLastNotification = true;
                }
            } else {
                // Para uso do RAIO, terceira situação
                $diffDays = $days - (int)$accountabilityDeadline->term;
                $lastDay = Carbon::now()->subDays($diffDays)->format('d/m/Y');
                $notificationMsg = "encerrou-se no dia $lastDay";
                $isLastNotification = true;
            }
            $this->rowSheet = $rowSheet;
            $this->handleInfoNotifications($notificationMsg, $notificationType, $isLastNotification, $days);
        }
    }

    private function handleInfoNotifications($notificationMsg, $notificationType, $isLastNotification, $days)
    {
        $rowSheetId = $this->rowSheet->id;
        $registration = App::i()->repo('Registration')->findOneBy(['id' => $this->rowSheet->registrationNumber]);

        $this->infosForNotifications[$rowSheetId]["registration_number"] = $registration->number;
        $this->infosForNotifications[$rowSheetId]["agent_name"] = $registration->owner->name;
        $this->infosForNotifications[$rowSheetId]["user_email"] = $registration->owner->user->email;
        $this->infosForNotifications[$rowSheetId]["notification_type"] = strtoupper($notificationType);
        $this->infosForNotifications[$rowSheetId]["is_last_notification"] = $isLastNotification;
        $this->infosForNotifications[$rowSheetId]["notification_msg"] = $notificationMsg;
        $this->infosForNotifications[$rowSheetId]["notification_msg"] = $notificationMsg;
        $this->infosForNotifications[$rowSheetId]["days_current"] = $days;
    }

    public function GET_registrationsInDiligence(): void
    {
        $app = App::i();

        if (!$app->request()->headers('MapasSDK-REQUEST')) {
            $this->json(['message' => 'Acesso não autorizado'], 401);
            return;
        }

        $limit  = isset($this->data['@limit'])  ? max(1, (int) $this->data['@limit'])  : 25;
        $page   = isset($this->data['@page'])   ? max(1, (int) $this->data['@page'])   : 1;
        $offset = isset($this->data['@offset']) ? max(0, (int) $this->data['@offset']) : $limit * ($page - 1);

        $diligences = $app->repo(Diligence::class)->findBy([
            'situation' => [
                Diligence::STATUS_OPEN,
                Diligence::STATUS_SEND,
                Diligence::STATUS_ANSWERED,
                Diligence::STATUS_COMPLETE,
            ],
        ]);

        $result = [];
        foreach ($diligences as $diligence) {
            $registration = $diligence->registration;
            $registrationId = $registration->id;

            if (isset($result[$registrationId])) {
                continue;
            }

            $rowSheet = $app->repo(RowSheet::class)->findOneBy(['registrationNumber' => $registrationId])
                ?: $app->repo(RowSheet::class)->findOneBy(['registrationNumber' => $registration->number]);
            if (!$rowSheet) {
                continue;
            }

            $result[$registrationId] = [
                'registration_number' => $registration->number,
                'diligence_situation' => $diligence->status,
                'row_sheet' => [
                    'municipality' => $rowSheet->municipality,
                    'instrument'   => $rowSheet->instrument,
                    'sacc'        => $rowSheet->saccNumber,
                ],
                'agent' => [
                    'name' => $diligence->agent->name,
                    'cpf'  => $diligence->agent->getMetadata('cpf'),
                ],
            ];
        }

        $result   = array_values($result);
        $total    = count($result);
        $numPages = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $page_result = array_slice($result, $offset, $limit);

        $this->json([
            'data' => $page_result,
            'meta' => [
                'total'    => $total,
                'page'     => $page,
                'limit'    => $limit,
                'numPages' => $numPages,
            ],
        ]);
    }

    public function GET_opportunitiesWithDiligence(): void
    {
        $app = App::i();

        if (!$app->request()->headers('MapasSDK-REQUEST')) {
            $this->json(['message' => 'Acesso não autorizado'], 401);
            return;
        }

        $limit  = isset($this->data['@limit'])  ? max(1, (int) $this->data['@limit'])  : 25;
        $page   = isset($this->data['@page'])   ? max(1, (int) $this->data['@page'])   : 1;
        $offset = isset($this->data['@offset']) ? max(0, (int) $this->data['@offset']) : $limit * ($page - 1);

        $conn = $app->em->getConnection();

        $sql = "
             SELECT
                o.id,
                o2.name             AS oportunidade_pai,
                o.name              AS nome,
                p.name              AS projeto,
                a.name              AS nome_agent,
                r.id                AS inscricao_id,
                r.number            AS inscricao_numero,
                r.status            AS inscricao_status,
                ra.name             AS inscricao_agente,
                am.value            AS inscricao_agente_cpf,
                rsi.sacc_number     AS sacc_number,
                rsi.instrument      AS instrument,
                rsi.municipality    AS municipality
            FROM opportunity o
            JOIN opportunity_meta om ON o.id = om.object_id
            JOIN agent a              ON o.agent_id = a.id
            JOIN opportunity o2       ON o.parent_id = o2.id
            JOIN project p            ON o.object_id = p.id
            JOIN seal_relation sr     ON sr.object_id = p.id
                                     AND sr.object_type = 'MapasCulturais\\Entities\\Project'
                                     AND sr.seal_id = 16
                                     AND sr.status >= 0
            LEFT JOIN registration r   ON r.opportunity_id = o.id
            LEFT JOIN agent ra         ON ra.id = r.agent_id
            LEFT JOIN agent_meta am    ON am.object_id = ra.id AND am.key = 'cpf'
            LEFT JOIN row_sheet_import rsi ON rsi.registration_number = r.number
                                          OR rsi.registration_number = r.id::varchar
            WHERE om.key = 'use_diligence'
              AND om.value = 'Sim'
              AND (a.parent_id = 5975 OR a.id = 5975)
              AND o.status <> -10
              AND o.parent_id IS NOT NULL
              AND o.id <> 6774
            ORDER BY o.id DESC, r.id ASC
        ";

        $rows     = $conn->fetchAllAssociative($sql);
        $total    = count($rows);
        $numPages = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $pageData = array_slice($rows, $offset, $limit);

        $this->json([
            'data' => $pageData,
            'meta' => [
                'total'    => $total,
                'page'     => $page,
                'limit'    => $limit,
                'numPages' => $numPages,
            ],
        ]);
    }

    /**
     * Altera o status para ão enviar mais notificações
     * @return void
     */
    public function POST_updateNotificationStatus()
    {
        if (!isset($this->data['access_token']) || $this->data['access_token'] !== $_ENV['ACCESS_TOKEN_API_EMAIL']) {
            $this->json(['message' => 'Acesso não autorizado'], 401);
        }

        $rowSheet = App::i()->repo(RowSheet::class)->findOneBy(['registrationNumber' => $this->data["registration_number"]]);

        $rowSheet->updateNotificationStatus();
    }
}
