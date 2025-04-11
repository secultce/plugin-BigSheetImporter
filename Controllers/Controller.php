<?php

namespace BigSheetImporter\Controllers;

use BigSheetImporter\Entities\RowSheet;
use BigSheetImporter\Exceptions\InvalidSheetFormat;
use BigSheetImporter\Services\SheetService;
use BigSheetImporter\Entities\Sheet;
use Carbon\Carbon;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use MapasCulturais\App;
use MapasCulturais\i;
use Shuchkin\{SimpleXLSX, SimpleXLS, SimpleXLSXGen};

class Controller extends \MapasCulturais\Controller
{
    private $infosForNotifications = [];
    private $rowSheet;

    public function POST_import(): void
    {
        $this->requireAuthentication();
        $tmpFilename = $_FILES['spreadsheet']['tmp_name'];

        $xlsData = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

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
        } catch (ConstraintViolationException $e) {
            $app->em->rollback();

            $detail = explode('DETAIL:  ', $e->getMessage())[1] ?? $e->getMessage();
            $pattern = '/Key \((.*?)\)=\((.*?)\) already exists\./';
            $constraint = i::__('Erro desconhecido no banco de dados.');

            if (preg_match($pattern, $detail, $matches)) {
                [ , $field, $value ] = $matches;
                $constraint = "Já existe um registro com o mesmo campo '$field': '$value'.";
            }

            $this->json([
                'error' => i::__('Ocorreu um erro ao importar um dado.'),
                'constraint' => $constraint,
            ], 419);
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

    public function GET_history(int $limit = 50, int $page = 1): void
    {
        $app = App::getInstance();
        if (!$app->user->isUserAdmin($app->user)) {
            $this->json('', 403);
            return;
        }

        try {
            $sheets = $app->em->getRepository(Sheet::class)->findHistory($limit, $page);
        } catch (\Exception $e) {
            $this->json(['message' => 'Unexpected error'], 500);
        }

        $this->json($sheets);
    }

    public function POST_validateSpreadsheet(): void
    {
        $this->requireAuthentication();
        $tmpFilename = $_FILES['spreadsheet']['tmp_name'];

        $xlsData = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

        $validate = SheetService::validate($xlsData);

        $this->json([
            'occurrences' => $validate->invalidData,
        ]);
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
     * Checagem dos dias das últimas notificações para não enviar em duplicidade
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
        $registration = App::i()->repo('Registration')->findOneBy(['number' => $this->rowSheet->registrationNumber]);

        $this->infosForNotifications[$rowSheetId]["registration_number"] = $registration->number;
        $this->infosForNotifications[$rowSheetId]["agent_name"] = $registration->owner->name;
        $this->infosForNotifications[$rowSheetId]["user_email"] = $registration->owner->user->email;
        $this->infosForNotifications[$rowSheetId]["notification_type"] = strtoupper($notificationType);
        $this->infosForNotifications[$rowSheetId]["is_last_notification"] = $isLastNotification;
        $this->infosForNotifications[$rowSheetId]["notification_msg"] = $notificationMsg;
        $this->infosForNotifications[$rowSheetId]["days_current"] = $days;
    }

    /**
     * Altera o status para não enviar mais notificações
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
