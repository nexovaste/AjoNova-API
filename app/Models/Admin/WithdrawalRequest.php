<?php

namespace App\Models\Admin;


use App\Models\Setup\SetupStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $primaryKey = 'withdrawal_request_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'withdrawal_type',
        'amount',
        'status_id',
        'reason',
        'withdraw_at',
        'attended_by',
        'attended_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'withdraw_at' => 'datetime',
        'attended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }
}
