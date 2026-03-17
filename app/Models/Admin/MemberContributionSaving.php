<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class MemberContributionSaving extends Model
{
    protected $primaryKey = 'member_contribution_saving';

    protected $fillable = [
        'user_id',
        'contribution_amount',
        'saving_amount',
        'created_by',
        'updated_by',
    ];

   

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
