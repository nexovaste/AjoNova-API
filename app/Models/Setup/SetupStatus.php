<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class SetupStatus extends Model
{
    protected $primaryKey = 'status_id'; 
    protected $fillable = ['status_name']; 
}
