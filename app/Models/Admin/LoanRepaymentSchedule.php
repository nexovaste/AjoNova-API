<?php

namespace App\Models\Admin;

use App\Models\Setup\SetupStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Predis\Response\Status;


class LoanRepaymentSchedule extends Model
{
    protected $primaryKey = 'loan_repayment_schedule_id';

    protected $fillable = [
        'user_id',
        'loan_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'repayment_amount',
        'interest_amount',
        'monthly_repayment',
        'amount_paid',
        'paid_at',
        'processed_by',
        'status_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'principal_amount' => 'decimal:2',
        'repayment_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'monthly_repayment' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }
}
