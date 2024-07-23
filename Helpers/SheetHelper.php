<?php

namespace BigSheetImporter\Helpers;

use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;

final class SheetHelper
{
    /**
     * @param SimpleXLSX|SimpleXLS $sheet
     * @return array
     */
    public static function validate($sheet): array
    {
        $invalidData = [];


        foreach ($sheet->rows() as $key => $row) {
            if($key === 0)
                continue;

            $invalidData = self::validateRow($row, $key, $invalidData);
        }

        return $invalidData;
    }

    public static function validateRow(array $row, int $rowIndex, array $invalidData = []): array
    {
        $row = array_map(function ($cell) {
            return $cell === '' ? null : $cell;
        }, $row);

        $app = App::getInstance();
        if (empty($app->repo(Registration::class)->findBy(['number' => $row[0]]))) {
            $invalidData[] = [$rowIndex, 0, 'Inscrição inválida', $row[0]];
        }
        if ($row[1] !== null && !!preg_match('/[0-9]{5}\.[0-9]{6}\/[0-9]{4}-[0-9]{2}/m', $row[1])) {
            $invalidData[] = [$rowIndex, 1, 'Processo inválido', $row[1]];
        }
        for ($k=6;$k<15;$k++) {
            if($row[$k] !== null && !self::validateDateString($row[$k]))
                $invalidData[] = [$rowIndex, $k, 'Formato de data inválida', $row[$k]];
        }

        return $invalidData;
    }

    public static function validateDateString(string $dateString): bool
    {
        return !!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/m', $dateString);
    }
}
