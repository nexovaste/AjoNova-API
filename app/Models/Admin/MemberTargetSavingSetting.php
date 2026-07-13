<?php

namespace App\Models\Admin;

use App\Models\Admin\MemberTargetSaving;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class MemberTargetSavingSetting extends Model
{
    protected $primaryKey = 'member_target_saving_setting_id';

    protected $fillable = [
        'user_id',
        'target_name',
        'target_amount',
        'duration_months',
        'monthly_amount',
        'start_date',
        'end_date',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function savings()
    {
        return $this->hasMany(MemberTargetSaving::class, 'member_target_saving_setting_id', 'member_target_saving_setting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
