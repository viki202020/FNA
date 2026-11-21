<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Support\FinancialYears;
use App\Support\VoucherNumber;

/**
 * Old bank lines (uploaded before years and bank sides were tracked) have only ONE entry, no voucher number and no year.
 * This gives each of them what an uploaded line has today: a voucher number, a financial year taken from its date,
 * and the bank's own entry, using a bank account you name. The lines stay uncategorised (status 0): both entries are
 * kept out of the accounts until each line is categorised. Nothing is deleted. By default it only shows what it would do.
 *
 *   php artisan fna:repair-legacy-bank-lines --merchant=1                 (preview)
 *   php artisan fna:repair-legacy-bank-lines --merchant=1 --apply         (do it)
 */
class RepairLegacyBankLines extends Command
{
    protected $signature = 'fna:repair-legacy-bank-lines {--merchant=} {--bank-name=Bank-PNB} {--apply}';
    protected $description = 'Give old single-entry bank lines a voucher number, a year and their bank-side entry';

    public function handle()
    {
        $merchant = (int) $this->option('merchant');
        if (!$merchant) { $this->error('Say which company: --merchant=ID'); return 1; }
        Session::start(); Session::put('merchant_id', $merchant); Session::put('id', $merchant);

        $lines = DB::table('statement')->where('merchant_id', $merchant)->whereNull('voucher_no')->whereNull('bank_id')->whereNull('fyear')
            ->where('status', 0)->where('opening', 0)->orderBy('st_date')->orderBy('id')->get();
        $dr = 0; $cr = 0; $skip = 0;
        foreach ($lines as $l) { $dr += (float) $l->debit; $cr += (float) $l->credit; if (!FinancialYears::acceptsDate($l->st_date)) { $skip++; } }
        $this->info(count($lines) . ' old line(s) found. Debits ' . number_format($dr, 2) . ', credits ' . number_format($cr, 2) . '.');
        if ($skip) { $this->warn($skip . ' of them fall in a closed or not-yet-open year and will be left alone.'); }
        if (!$this->option('apply')) { $this->line('Preview only. Add --apply to make the change.'); return 0; }

        $name = $this->option('bank-name');
        DB::transaction(function () use ($lines, $merchant, $name) {
            $bank = DB::table('head')->where('account_type_id', 3)->where('name', $name)->where('merchant', $merchant)->first();
            $bankId = $bank ? $bank->id : DB::table('head')->insertGetId([
                'account_type_id' => 3, 'name' => $name, 'status' => 1, 'merchant' => $merchant, 'created_at' => date('d-m-Y H:i:s'), 'created_by' => $merchant,
            ]);
            $numbers = VoucherNumber::sequence('SBV-', 5);
            $n = 0;
            foreach ($lines as $l) {
                if (!FinancialYears::acceptsDate($l->st_date)) { continue; }
                $fy = FinancialYears::yearForDate($l->st_date);
                $no = $numbers->next();
                $vid = DB::table('vouchers')->insertGetId([
                    'merchant_id' => $merchant, 'voucher_no' => $no, 'series' => 'SBV', 'fyear' => $fy, 'voucher_date' => date('Y-m-d', strtotime($l->st_date)),
                    'status' => 'posted', 'version' => 1, 'created_by' => $merchant, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'created', 'reason' => 'Old bank line given its bank-side entry', 'changed_by' => $merchant, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('statement')->where('id', $l->id)->update(['voucher_no' => $no, 'voucher_id' => $vid, 'bank_id' => $bankId, 'fyear' => $fy]);
                DB::table('statement')->insert([
                    'voucher_no' => $no, 'voucher_id' => $vid, 'head_id' => $bankId, 'bank_id' => $bankId,
                    'debit' => $l->credit, 'credit' => $l->debit, 'remark' => $l->remark, 'st_date' => $l->st_date, 'fyear' => $fy,
                    'status' => 0, 'opening' => 0, 'merchant_id' => $l->merchant_id, 'created_at' => date('d-m-Y H:i:s'), 'created_by' => $merchant,
                ]);
                $n++;
            }
            $this->info($n . ' line(s) repaired. Bank account "' . $name . '" is head ' . $bankId . '.');
        });
        $this->line('The lines are still uncategorised. Categorise them on Uncategorised entry; both entries then reach the accounts together.');
        return 0;
    }
}
