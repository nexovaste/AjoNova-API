<?php

namespace App\Models\Setup;

use Illuminate\Database\Eloquent\Model;

class SetupCounter extends Model
{
    protected $primaryKey = 'counter_id'; 
    public $incrementing = false; 
    protected $keyType = 'string';

    protected $fillable = ['counter_id', 'counter_value', 'counter_description']; 

    public static function generateCustomId($counterId)
    {
        $counter = self::where('counter_id', $counterId)->first();
        $counter->increment('counter_value');
        $currentValue = $counter->counter_value;
        if ($currentValue < 10) {$no = '00' . $currentValue;} 
        elseif ($currentValue >= 10 && $currentValue < 100) {$no = '0' . $currentValue;}
        else{$no = $currentValue;}
        return $counterId . $no . date('YmdHis') . rand(100000, 999999);
    }
}
