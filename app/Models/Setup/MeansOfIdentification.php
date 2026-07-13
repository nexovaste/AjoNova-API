<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class MeansOfIdentification extends Model
{
    protected $primaryKey = 'means_of_identification_id'; 
    protected $fillable = ['means_of_identification_name']; 
}
