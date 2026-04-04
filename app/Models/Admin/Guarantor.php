<?php

namespace App\Models\Admin;

use App\Models\Setup\MeansOfIdentification;
use App\Models\Setup\SetupGender;
use App\Models\Setup\SetupTitle;
use Illuminate\Database\Eloquent\Model;
use Predis\Response\Status;

class Guarantor extends Model
{
    protected $primaryKey = 'guarantor_id';

    protected $fillable = [
        'loan_id',
        'title_id',
        'gender_id',
        'first_name',
        'last_name',
        'middle_name',
        'phone_number',
        'email',
        'address',
        'occupation',
        'means_of_identification_id',
        'id_number',
        'relationship_to_borrower',
        // 'guarantor_user_id',
        'guaranteed_amount',
        // 'approved_at',
        'status_id',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }


    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'status_id');
    }

    public function title()
    {
        return $this->belongsTo(SetupTitle::class, 'title_id', 'title_id');
    }

    public function gender()
    {
        return $this->belongsTo(SetupGender::class, 'gender_id', 'gender_id');
    }

    public function meansOfIdentification()
    {
        return $this->belongsTo(MeansOfIdentification::class, 'means_of_identification_id', 'means_of_identification_id');
    }
}
