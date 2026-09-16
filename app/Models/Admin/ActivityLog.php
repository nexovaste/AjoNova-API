<?php

namespace App\Models\Admin;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityLog extends Model
{
    protected $primaryKey = 'activity_log_id';
    public $timestamps = false;

    protected $fillable = [
        'performed_by',
        'role_id',
        'user_type',
        'action',
        'description',
        'ip_address',
        'device',
        'browser',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $hidden = [
        'metadata'
    ];

    /**
     * Relationship for activity log reads (ReadActivity).
     * Fixes the "Call to undefined method ActivityLog::reads()" error.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ReadActivity::class, 'activity_log_id', 'activity_log_id');
    }

    public function performedByStaff()
    {
        return $this->belongsTo(Staff::class, 'performed_by', 'staff_id');    
    }

    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');    
    }
}