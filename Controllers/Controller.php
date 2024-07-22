<?php

namespace BigSheetImporter\Controllers;

use BigSheetImporter\Entities\RowSheet;
use BigSheetImporter\Entities\Sheet;
use MapasCulturais\Entities\Registration;
use Shuchkin\{SimpleXLSX, SimpleXLS};

class Controller extends \MapasCulturais\Controller
{
    public function POST_import(): void
    {
//        $this->requireAuthentication();
        $tmpFilename = $_FILES['first']['tmp_name'];

        $sheet = SimpleXLSX::parse($tmpFilename) ?: SimpleXLS::parse($tmpFilename);

        echo '<table>';
        foreach ($sheet->rows() as $key => $row) {
            echo '<tr>';
            foreach ($row as $k => $cell) {
                if ($k > 5 && $k < 15) {
                    echo "<td>";
                        var_dump($cell);
                    echo "</td>";
                }
            }
            echo '</tr>';
        }
        echo '</table>';
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
