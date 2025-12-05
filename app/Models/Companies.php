<?php

namespace App\Models;

use app\Models\Employees;
use Illuminate\Database\Eloquent\Model;

class Companies extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'email',
        'logo',
        'website'
    ];

    public function employees()
    {
        return $this->hasMany(Employees::class);
    }
}
