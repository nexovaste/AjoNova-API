<?php

namespace App\Models\Admin;

use App\Models\Setup\SetupLga;
use App\Models\Setup\SetupTitle;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupStatus;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Staff extends Authenticatable
{
    use HasRoles, HasApiTokens, Notifiable;
    protected $guard_name = 'admin';
    protected $primaryKey = 'staff_id'; 
    public $incrementing = false; 
    protected $keyType = 'string';

    protected $fillable = [
        'staff_id',
        'title_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender_id',
        'email',
        'mobile_number',
        'home_address',
        'date_of_birth',
        'lga_id',
        'nin',
        'passport',
        'status_id',
        'password',
        'created_by',
        'updated_by',
        'login_attempt',
        'last_login_at', 
    ]; 
    protected $hidden = ['password'];
    protected $casts = [
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function gender()
    {
        return $this->belongsTo(SetupGender::class, 'gender_id', 'gender_id');
    }

    public function title()
    {
        return $this->belongsTo(SetupTitle::class, 'title_id', 'title_id');
    }

    public function lga()
    {
        return $this->belongsTo(SetupLga::class, 'lga_id', 'lga_id');
    }

    const DEFAULT_PASSPORT = 'default.png';

}
