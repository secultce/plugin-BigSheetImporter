<?php

namespace BigSheetImporter\Controllers;

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
}
