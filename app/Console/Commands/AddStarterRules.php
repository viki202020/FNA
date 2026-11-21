<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Adds the two rules agreed in discussion (idempotent: a rule that already exists is left alone):
 *   wording contains TRIPLE PLAY / TRIPLEPLAY  -> Accounts Payable      (money out)
 *   wording contains AMB NON MAINTENANCE        -> Bank Service Charges  (money out)
 * Rules never categorise a line whose amount equals an open invoice or bill.
 *
 *   php artisan fna:add-starter-rules --merchant=1
 */
class AddStarterRules extends Command
{
    protected $signature = 'fna:add-starter-rules {--merchant=}';
    protected $description = 'Add the Triple Play and AMB charge rules';

    public function handle()
    {
        $merchant = (int) $this->option('merchant');
        if (!$merchant) { $this->error('Say which company: --merchant=ID'); return 1; }
        $payable = DB::table('head')->where('name', 'Accounts Payable')->orderBy('id')->value('id');
        $charges = DB::table('head')->where('name', 'Bank Service Charges')->orderBy('id')->value('id');
        if (!$payable || !$charges) { $this->error('Accounts Payable or Bank Service Charges was not found.'); return 1; }
        $rules = [
            ['Triple Play', 'TRIPLE PLAY', $payable],
            ['Triple Play (one word)', 'TRIPLEPLAY', $payable],
            ['AMB non-maintenance charge', 'AMB NON MAINTENANCE', $charges],
        ];
        foreach ($rules as [$name, $content, $head]) {
            $exists = DB::table('rules')->where('merchant_id', $merchant)->where('col_type', 'Description')->whereRaw('UPPER(content) = ?', [$content])->where('head_id', $head)->exists();
            if ($exists) { $this->line("Already there: $name"); continue; }
            DB::table('rules')->insert([
                'rule_name' => $name, 'rule_for' => 'Debit', 'rule_in' => null, 'col_type' => 'Description', 'rule_condition' => 'Any', 'content' => $content,
                'rule_action_type' => 'Debit', 'head_id' => $head, 'col_type1' => 'Amount', 'rule_condition1' => 'Any', 'content1' => null,
                'status' => 1, 'merchant_id' => $merchant, 'created_at' => date('d-m-Y H:i:s'), 'created_by' => $merchant,
            ]);
            $this->info("Added: $name (wording contains $content, money out)");
        }
        return 0;
    }
}
