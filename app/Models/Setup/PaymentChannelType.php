<?php

namespace App\Models\Setup;

use App\Models\Admin\LoanRepayment;
use Illuminate\Database\Eloquent\Model;

class PaymentChannelType extends Model
{
    protected $primaryKey = 'payment_channel_type_id';
    protected $fillable = ['payment_channel_type_name'];

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'payment_channel_type_id');
    }
}


