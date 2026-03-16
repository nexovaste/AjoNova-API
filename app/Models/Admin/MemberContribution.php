<?php

namespace App\Models\Admin;

use App\Models\Setup\PaymentChannelType;
use App\Models\User\User;
use Predis\Response\Status;
use Illuminate\Database\Eloquent\Model;

class MemberContribution extends Model
{
    protected $primaryKey = 'member_contribution_id';

    protected $fillable = [
        'user_id',
        'amount',
        'contribution_date',
        'payment_channel_type_id',
        'contribution_month',
        'contribution_year',
        'reference',
        'ledger_entry_id',
        'status_id',
        'processed_by',
    ];

    protected $casts = [
        'contribution_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'status_id');
    }

    public function ledger()
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id', 'ledger_entry_id');
    }

    public function paymentChannel()
    {
        return $this->belongsTo(PaymentChannelType::class, 'payment_channel_type_id', 'payment_channel_type_id');
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('contribution_month', $month)->where('contribution_year', $year);
    }
}
