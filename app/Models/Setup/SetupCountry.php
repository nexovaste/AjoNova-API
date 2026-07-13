<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class SetupCountry extends Model
{
    protected $primaryKey = 'country_id';

    protected $fillable = [
        'country_name',
        'country_code',
    ];

    public function states()
    {
        return $this->hasMany(SetupState::class, 'country_id');
    }
}
