<?php
namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class FormulaHelper {
    public static function parseFormula($formula, $variableSet)
        {
            // here we take all the vars that are in ${variable}
            preg_match_all('/\$\{(.*?)}/', $formula, $matches);
            // here we set the values to the variables
            if ($matches) {
                foreach ($matches[0] as $key => $match) {
                    $variableToken = $match;
                    $variableName = $matches[1][$key];
                    if ($variableSet && $variableSet[$variableName]) {
                        $value = $variableSet[$variableName];
                    } else {
                        $value = 0;
                    }
                    $formula = str_replace($variableToken, $value, $formula);
                }
            }
            // Here we get the value of the formula
            return $formula;
            // $spreadsheet = new Spreadsheet();
            // $sheet = $spreadsheet->getActiveSheet();
            // $sheet->setCellValue('A1', '=' . $formula);
            // return $sheet->getCell('A1')->getCalculatedValue();
        }
}
