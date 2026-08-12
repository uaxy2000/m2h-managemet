<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaInsight extends Model
{
    protected $fillable = [
        'date', 'entity_type', 'entity_id', 'entity_name', 'parent_entity_id',
        'spend', 'impressions', 'clicks', 'leads_count',
        'cpm', 'cpc', 'ctr', 'synced_at',
    ];

    protected $casts = [
        'date'       => 'date',
        'spend'      => 'decimal:2',
        'cpm'        => 'decimal:4',
        'cpc'        => 'decimal:4',
        'ctr'        => 'decimal:4',
        'synced_at'  => 'datetime',
    ];

    public function getCplAttribute(): float
    {
        return $this->leads_count > 0 ? round($this->spend / $this->leads_count, 2) : 0;
    }
}
