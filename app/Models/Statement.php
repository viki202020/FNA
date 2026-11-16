<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    use HasFactory;
    protected $table = 'statement';
    protected $fillable = [
        'head_id','debit','credit','bank_id','fyear','remark','st_date','voucher_no','merchant_id','created_at','created_by','status'
    ];
}
