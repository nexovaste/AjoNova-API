<?php

namespace App\Models\Setup;

use App\Models\User\User;
use App\Models\Admin\Staff;
use Illuminate\Database\Eloquent\Model;

class SetupLga extends Model
{
    protected $primaryKey = 'lga_id';

    protected $fillable = [
        'state_id',
        'lga_name',
    ];

    public function state()
    {
        return $this->belongsTo(SetupState::class, 'state_id', 'state_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'lga_id', 'lga_id');
    }

    public function staff()
    {
        return $this->hasMany(Staff::class, 'lga_id', 'lga_id');
    }
}

