<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    use HasFactory;
    protected $table = 'statement';
    protected $fillable = [
        'head_id','debit','credit','bank_id','fyear','remark','st_date','voucher_no','merchant_id','created_at','created_by','status','voucher_id'
    ];

    /**
     * The one rule for "does this line count in the books": categorised (status 1).
     * Every report must use it so uncategorised lines never reach a Balance Sheet or P&L.
     */
    public function scopeEffective($query)
    {
        return $query->where('statement.status', 1);
    }
}
