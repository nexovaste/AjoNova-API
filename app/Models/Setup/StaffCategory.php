<?php

namespace App\Models\Setup;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class StaffCategory extends Model
{
    protected $primaryKey = 'staff_category_id';

    protected $fillable = ['staff_category_name'];

    public function users()
    {
        return $this->hasMany(User::class, 'staff_category_id');
    }
}

