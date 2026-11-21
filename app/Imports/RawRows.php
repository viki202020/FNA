<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/** Reads a spreadsheet as plain rows (used where the page lets the user say which column is which). */
class RawRows implements ToArray
{
    public function array(array $array) { }
}
