<?php

namespace App\Models\User;

use App\Models\Admin\Guarantor;
use App\Models\Admin\Loan;
use App\Models\Admin\MemberContribution;
use App\Models\Admin\MemberTargetSaving;
use App\Models\Admin\Wallet;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupLga;
use App\Models\Setup\SetupStatus;
<<<<<<< HEAD
use App\Models\Setup\SetupTitle;
=======
use App\Models\Admin\MemberContribution;
use App\Models\Admin\MemberTargetSaving;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
>>>>>>> 0ebe7631eb54c115a36e610bba9cfffb53b1e462
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'membership_number',
        'title_id',
        'staff_category_id',
        'membership_type_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender_id',
        'email',
        'mobile_number',
        'home_address',
        'lga_id',
        'nin',
        'passport',
        'status_id',
        'password',
        'monthly_salary',
        'date_joined',
        'date_exited',
        'created_by',
        'updated_by',
        'login_attempt',
        'last_login_at',
    ];


    protected $hidden = ['password',];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_joined' => 'date',
        'date_exited' => 'date',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function title()
    {
        return $this->belongsTo(SetupTitle::class, 'title_id', 'title_id');
    }

    public function gender()
    {
        return $this->belongsTo(SetupGender::class, 'gender_id', 'gender_id');
    }

    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function lga()
    {
        return $this->belongsTo(SetupLga::class, 'lga_id', 'lga_id');
    }

 
    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id');
    }

    public function contributions()
    {
        return $this->hasMany(MemberContribution::class, 'user_id', 'user_id');
    }

    public function targetSavings()
    {
        return $this->hasMany(MemberTargetSaving::class, 'user_id', 'user_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id');
    }

    public function guarantorLoans()
    {
        return $this->hasMany(Guarantor::class, 'guarantor_user_id', 'user_id');
    }

    const DEFAULT_PASSPORT = 'default.png';
}
