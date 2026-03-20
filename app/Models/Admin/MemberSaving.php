<?php

namespace App\Models\Admin;

use App\Models\Setup\PaymentChannelType;
use App\Models\Setup\SetupStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;


class MemberSaving extends Model
{
    protected $primaryKey = 'member_saving_id';

    protected $fillable = [
        'user_id',
        'saving_amount',
        'saving_date',
        'payment_channel_type_id',
        'reference',
        'ledger_entry_id',
        'status_id',
        'processed_by',
    ];

    protected $casts = [
        'saving_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function ledger()
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id', 'ledger_entry_id');
    }

    public function paymentChannel()
    {
        return $this->belongsTo(PaymentChannelType::class, 'payment_channel_type_id', 'payment_channel_type_id');
    }
}
