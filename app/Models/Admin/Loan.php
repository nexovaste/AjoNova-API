<?php

namespace App\Models\Admin;
use App\Models\Setup\SetupStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $primaryKey = 'loan_id';

    protected $fillable = [
        'user_id',
        'loan_policy_id',
        'duration_months',
        'principal_amount',
        'interest_amount',
        'total_payable',
        'outstanding_balance',
        'loan_reference',
        'requested_at',
        'disbursed_at',
        'approved_by',
        'approved_at',
        'status_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function policy()
    {
        return $this->belongsTo(LoanPolicy::class, 'loan_policy_id');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'loan_id');
    }

    public function guarantors()
    {
        return $this->hasMany(Guarantor::class, 'loan_id');
    }

     public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }
}




