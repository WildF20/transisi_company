<?php

namespace App\Models;

use App\Models\Companies;
use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'company_id',
        'name',
        'email'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
