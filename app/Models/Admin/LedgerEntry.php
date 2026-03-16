<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;


class LedgerEntry extends Model
{
    protected $primaryKey = 'ledger_entry_id';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'entry_type',
        'transaction_type',
        'source_table',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'wallet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
