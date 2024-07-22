<?php

namespace BigSheetImporter\Helpers;

use BigSheetImporter\Entities\ImportOccurrence;
use BigSheetImporter\Entities\Sheet;
use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;

final class SheetHelper
{
    /**
     * @param SimpleXLSX|SimpleXLS $sheet
     * @return bool
     */
    public static function validate($sheet): bool
    {
        $valid = true;


        foreach ($sheet->rows() as $key => $row) {
            if($key === 0) continue;
            self::validateRow($row, $valid);
        }

        return $valid;
    }

    public static function validateRow(array $row, &$valid): void
    {
        $row = array_map(function ($cell) {
            return $cell === '' ? null : $cell;
        }, $row);

        $app = App::getInstance();
        $invalidRegistration = empty($app->repo(Registration::class)->findBy(['number' => $row[0]]));
        $invalidProcess = is_null($row[1])
            ? $row[1]
            : !!preg_match('/[0-9]{5}\.[0-9]{6}\/[0-9]{4}-[0-9]{2}/m', $row[1]);
        $invalidDate = [];
        for ($k=6;$k<15;$k++) {
            if($row[$k] !== null)
                $invalidDate[] = self::validateDateString($row[$k]);
        }

        foreach (array_keys($invalidDate, true) as $index) {
            $index +=6;

        }
        /** @todo: Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate,
         *         Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate,
         *         Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate, Validate
         */

    }

    public static function validateDateString(string $dateString): bool
    {
        return !!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/m', $dateString);
    }

    /**
     * @param bool[] $occurrences
     * @param Sheet $sheet
     * @return array
     */
    public static function generateOccurrences(array $occurrences, Sheet $sheet): array
    {
        $occurrences = array_map(function ($occurrence, $key) use ($sheet) {
            return new ImportOccurrence($occurrence, $sheet, $key + 1);
        }, $occurrences, array_keys($occurrences));

        return $occurrences;
    }
}
