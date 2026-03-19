<?php

namespace App\Models\Admin;


use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Predis\Response\Status;

class MemberTargetSaving extends Model
{
    protected $primaryKey = 'member_target_saving_id';

    protected $fillable = [
        'user_id',
        'member_target_saving_setting_id',
        'monthly_amount',
        'current_amount',
        'payment_channel_type_id',
        'reference',
        'ledger_entry_id',
        'status_id',
        'processed_by'
    ];

    public function setting()
    {
        return $this->belongsTo(MemberTargetSavingSetting::class, 'member_target_saving_setting_id', 'member_target_saving_setting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

     public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'status_id');
    }
}