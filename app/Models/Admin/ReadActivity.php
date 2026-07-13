<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ReadActivity extends Model
{
    protected $primaryKey = 'activity_log_id';
    public $timestamps = false;
    protected $fillable = [
        'activity_log_id',
        'staff_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class, 'activity_log_id', 'activity_log_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
}
