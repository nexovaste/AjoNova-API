<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class SetupState extends Model
{
    protected $primaryKey = 'state_id';

    protected $fillable = [
        'country_id',
        'state_name',
    ];

    public function country()
    {
        return $this->belongsTo(SetupCountry::class, 'country_id');
    }

    public function lgas()
    {
        return $this->hasMany(SetupLga::class, 'state_id');
    }
}

