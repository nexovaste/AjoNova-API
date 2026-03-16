<?php

namespace App\Models\Admin;

use Predis\Response\Status;
use Illuminate\Database\Eloquent\Model;


class LoanRepaymentSchedule extends Model
{
    protected $primaryKey = 'loan_repayment_schedule_id';

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_due',
        'amount_paid',
        'status_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
        'amount_paid' => 'decimal:2'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'status_id');
    }
}
