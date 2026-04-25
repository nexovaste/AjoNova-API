<?php

namespace App\Models\Admin;

use App\Models\Setup\SetupStatus;
use Illuminate\Database\Eloquent\Model;

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


    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }


}
