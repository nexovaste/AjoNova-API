<?php

namespace App\Models\Admin;

use App\Models\Setup\SetupStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    protected $primaryKey = 'guarantor_id';

    protected $fillable = [
        'loan_id',
        'guarantor_user_id',
        'guaranteed_amount',
        'approved_at',
        'status_id',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'guarantor_user_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsTo(SetupStatus::class, 'status_id', 'status_id');
    }
}
