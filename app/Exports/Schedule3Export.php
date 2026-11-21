<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/** The balance sheet and profit and loss as rows in a spreadsheet. */
class Schedule3Export implements FromArray, ShouldAutoSize
{
    private $s; private $entity;
    public function __construct($s, $entity) { $this->s = $s; $this->entity = $entity; }

    public function array(): array
    {
        $s = $this->s; $prev = $s['prev_year'];
        $rows = [[$this->entity ? $this->entity->name : ''], ['Balance sheet as at ' . $s['year'] . ' (amounts in ' . $s['unit_name'] . ')'], ['Particulars', 'Note', $s['year'], $prev ?: '']];
        $n = fn($v) => $v === null ? '' : $v;
        $rows[] = ['EQUITY AND LIABILITIES'];
        foreach ($s['bs']['equity'] as $sec) { $rows[] = [$sec['title']]; foreach ($sec['items'] as $i) { $rows[] = ['   ' . $i['label'], $n($i['note']), $i['cur'], $n($i['prev'])]; } $rows[] = ['   Total ' . $sec['title'], '', $sec['cur'], $n($sec['prev'])]; }
        $rows[] = ['TOTAL EQUITY AND LIABILITIES', '', $s['bs']['equity_total']['cur'], $n($s['bs']['equity_total']['prev'])];
        $rows[] = ['ASSETS'];
        foreach ($s['bs']['assets'] as $sec) { $rows[] = [$sec['title']]; foreach ($sec['items'] as $i) { $rows[] = ['   ' . $i['label'], $n($i['note']), $i['cur'], $n($i['prev'])]; } $rows[] = ['   Total ' . $sec['title'], '', $sec['cur'], $n($sec['prev'])]; }
        $rows[] = ['TOTAL ASSETS', '', $s['bs']['assets_total']['cur'], $n($s['bs']['assets_total']['prev'])];
        $rows[] = []; $rows[] = ['Statement of profit and loss for the year ended ' . $s['year'] . ' (amounts in ' . $s['unit_name'] . ')'];
        $p = $s['pl'];
        $rows[] = ['I. Revenue from operations', $n($p['revenue']['note']), $p['revenue']['cur'], $n($p['revenue']['prev'])];
        $rows[] = ['II. Other income', $n($p['other_income']['note']), $p['other_income']['cur'], $n($p['other_income']['prev'])];
        $rows[] = ['III. Total income', '', $p['total_income']['cur'], $n($p['total_income']['prev'])];
        $rows[] = ['IV. Expenses'];
        foreach ($p['expenses'] as $e) { $rows[] = ['   ' . $e['label'], $n($e['note']), $e['cur'], $n($e['prev'])]; }
        $rows[] = ['   Total expenses', '', $p['total_expenses']['cur'], $n($p['total_expenses']['prev'])];
        $rows[] = ['V. Profit before tax', '', $p['pbt']['cur'], $n($p['pbt']['prev'])];
        $rows[] = ['VI. Tax expense', $n($p['tax']['note']), $p['tax']['cur'], $n($p['tax']['prev'])];
        $rows[] = ['VII. Profit for the year', '', $p['profit']['cur'], $n($p['profit']['prev'])];
        return $rows;
    }
}
