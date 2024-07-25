<?php

namespace BigSheetImporter\Services;

use BigSheetImporter\Entities\ImportOccurrence;
use BigSheetImporter\Entities\RowSheet;
use BigSheetImporter\Entities\Sheet;
use BigSheetImporter\Exceptions\InvalidSheetFormat;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MapasCulturais\{
    App,
    i,
    Entities\Registration,
};
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;

final class SheetService
{
    /**
     * @param SimpleXLSX|SimpleXLS $xlsSheet
     * @return object
     */
    public static function validate(object $xlsSheet): object
    {
        $invalidData = [];
        $invalidRows = [];

        foreach ($xlsSheet->rows() as $key => $row) {
            if($key === 0)
                continue;

            $invalidCount = count($invalidData);
            $invalidData = self::validateRow($row, $key+1, $invalidData);
            if (count($invalidData) > $invalidCount) {
                $invalidRows[] = $key;
            }
        }

        return (object)compact('invalidData', 'invalidRows');
    }

    /**
     * @throws InvalidSheetFormat
     */
    public static function validateRow(array $row, int $rowIndex, array $invalidData = []): array
    {
        if (count($row) !== 19) {
            throw new InvalidSheetFormat('Número de colunas inválido', 400);
        }

        /** Altera células vazias para NULL */
        $row = array_map(function ($cell) {
            return $cell === '' ? null : $cell;
        }, $row);

        $app = App::getInstance();
        if (empty($app->repo(Registration::class)->findBy(['number' => $row[0]]))) {
            $invalidData[] = self::newInvalidObject(
                $rowIndex,
                chr(65+0),
                i::__('Inscrição inexistente'),
                $row[0]
            );
        }
        if ($row[1] !== null && !!preg_match('/[0-9]{5}\.[0-9]{6}\/[0-9]{4}-[0-9]{2}/m', $row[1])) {
            $invalidData[] = self::newInvalidObject(
                $rowIndex,
                chr(65+1),
                i::__('Processo inválido'),
                $row[1]
            );
        }
        for ($k=6;$k<16;$k++) {
            if($row[$k] !== null && !self::validateDateString($row[$k]))
                $invalidData[] = self::newInvalidObject(
                    $rowIndex,
                    chr(65+$k),
                    i::__('Formato de data inválida'),
                    $row[$k]
                );
        }

        return $invalidData;
    }

    public static function validateDateString(string $dateString): bool
    {
        return !!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/m', $dateString);
    }

    private static function newInvalidObject(int $rowIndex, string $columnIndex, string $message, $value): object
    {
        return (object)compact('rowIndex', 'columnIndex', 'message', 'value');
    }

    /**
     * @param array $invalidData
     * @param Sheet $sheet
     * @return Collection
     */
    public static function createOccurrences(array $invalidData, Sheet $sheet): Collection
    {
        $occurrences = new ArrayCollection();
        foreach ($invalidData as $occurrence) {
            $occurrence->sheet = $sheet;
            $occurrences[] = new ImportOccurrence(...array_values((array)$occurrence));
        }
        return $occurrences;
    }

    /**
     * @param
     */
    public static function createRows(array $xlsDataRows, Sheet $sheet, array $invalidRows): Collection
    {
        $rows = new ArrayCollection();
        foreach ($xlsDataRows as $key => $row) {
            if($key === 0 || in_array($key, $invalidRows))
                continue;

            $row = array_map(function ($cell) {
                return $cell === '' ? null : $cell;
            }, $row);

            $app = App::getInstance();
            $rowSheet = $app->repo(RowSheet::class)->findOneBy(['registrationNumber' => $row[0]]) ?: new RowSheet();
            $rowSheet->registrationNumber = $row[0];
            array_shift($row);
            $rowSheet->setRowSheet(...$row);
            $rowSheet->sheet = $sheet;
            $rows[] = $rowSheet;
        }
        return $rows;
    }
}
