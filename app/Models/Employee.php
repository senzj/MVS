<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    // Remove SoftDeletes trait since you're using is_archived instead
    
    protected $fillable = [
        'name',
        'status',
        'contact_number',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_archived', false);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive')->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    // Accessors
    public function getStatusColorAttribute()
    {
        if ($this->is_archived) {
            return 'gray';
        }
        
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'yellow',
            default => 'gray'
        };
    }

    public function getDisplayStatusAttribute()
    {
        if ($this->is_archived) {
            return 'Archived';
        }
        
        return ucfirst($this->status);
    }
}
