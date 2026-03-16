<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Predis\Response\Status;

class LoanPolicy extends Model
{
    protected $primaryKey = 'loan_policy_id';

    protected $fillable = [
        'loan_multiplier',
        'minimum_amount',
        'maximum_amount',
        'min_duration_months',
        'max_duration_months',
        'interest_rate',
        'processing_fee',
        'penalty_rate',
        'eligibility_months',
        'allow_multiple_loans',
        'status_id',
        'created_by',
        'updated_by',
    ];


    public function loans()
    {
        return $this->hasMany(Loan::class, 'loan_policy_id', 'loan_policy_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'status_id');
    }

    public function isValidDuration($months)
    {
        return $months >= $this->min_duration_months &&
            $months <= $this->max_duration_months;
    }
}
