<?php

namespace App\Models\Setup;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;


class MembershipType extends Model
{
    protected $primaryKey = 'membership_type_id';
    protected $fillable = [
        'membership_type_name',
        'can_take_loan'
    ];

    protected $casts = [
        'can_take_loan' => 'boolean'
    ];


    public function users()
    {
        return $this->hasMany(User::class, 'membership_type_id');
    }
}
