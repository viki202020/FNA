<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasFactory;
    protected $table = 'rules';
    protected $fillable = ['col_type','rule_condition','content','rule_action_type','head_id','col_type1','rule_condition1','content1','updated_at','updated_by',
        // These were missing, so editing a rule silently ignored them (name, direction, bank, tags, on/off).
        'rule_name','rule_for','rule_in','category','customer','supplier','status','merchant_id','created_at','created_by'];
}
