<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityFinancialYear extends Model
{
    use HasFactory;
    protected $table = 'entity_financial_year';
    public $timestamps = false;
}
