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
        'target_name',
        'target_amount',
        'monthly_amount',
        'current_amount',
        'start_date',
        'end_date',
        'status_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

        public function status()
        {
            return $this->belongsTo(Status::class, 'status_id', 'status_id');
        }
}
