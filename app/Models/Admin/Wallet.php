<?php

namespace App\Models\Admin;

use App\Models\User\User;
use App\Models\Setup\SetupStatus;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $primaryKey = 'wallet_id';
    protected $fillable = [
        'user_id',
        'total_saving_amount',
        'total_target_amount',
        'total_contributions',
        'outstanding_loan_balance',
        'locked_balance',
        'status_id',
    ];

    protected $casts = [
        'savings_balance' => 'decimal:2',
        'outstanding_loan_balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
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
