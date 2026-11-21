<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Session;
use App\Support\ReturnDetector;

/** php artisan fna:detect-returns --merchant=1 : looks for returned payments among the uncategorised bank lines. */
class DetectReturnedPayments extends Command
{
    protected $signature = 'fna:detect-returns {--merchant=}';
    protected $description = 'Recognise and reverse payments the bank has returned';

    public function handle()
    {
        $m = (int) $this->option('merchant');
        if (!$m) { $this->error('Say which company: --merchant=ID'); return 1; }
        Session::start(); Session::put('merchant_id', $m); Session::put('id', $m);
        $this->info(ReturnDetector::scan() . ' returned payment(s) recognised and reversed.');
        return 0;
    }
}
