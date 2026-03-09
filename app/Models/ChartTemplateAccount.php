<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartTemplateAccount extends Model
{
    protected $fillable = [
        'chart_template_id', 'number', 'name', 'type',
        'parent_number', 'is_header', 'sort_order',
    ];

    protected $casts = [
        'is_header' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(ChartTemplate::class, 'chart_template_id');
    }
}
