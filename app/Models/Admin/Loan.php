<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $primaryKey = 'loan_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'loan_id',
        'user_id',
        'duration_months',
        'principal_amount',
        'interest_amount',
        'loan_reference',
        'requested_at',
        'disbursed_at',
        'attended_by',
        'attended_at',
        'rejection_reason',
        'status_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'loan_id');
    }

    public function guarantors()
    {
        return $this->hasMany(Guarantor::class, 'loan_id');
    }
}
