<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class SetupTitle extends Model
{
    protected $primaryKey = 'title_id'; 
    protected $fillable = ['title_name']; 
}
