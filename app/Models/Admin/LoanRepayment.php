<?php

namespace App\Models\Admin;

use App\Models\Setup\PaymentChannelType;
use App\Models\Setup\SetupStatus;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $table = 'loan_repayments';
    protected $primaryKey = 'loan_repayment_id';

    protected $fillable = [
        'loan_id',
        'amount_paid',
        'balance_before',
        'balance_after',
        'repayment_date',
        'status_id',
        'payment_reference',
        'payment_channel_type_id',
        'ledger_entry_id',
        'processed_by',
    ];

    protected $casts = [
        'amount_paid'    => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
        'repayment_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id', 'ledger_entry_id');
    }

    public function paymentChannel()
    {
        return $this->belongsTo(PaymentChannelType::class, 'payment_channel_type_id', 'payment_channel_type_id');
    }

     public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }

    public function scopeForLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId);
    }
}
