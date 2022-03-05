<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Filters\Filterable;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use Filterable;

    protected $fillable = [
        'name',
        'slug',
        'menu_name',
        'menu_link',
        'title',
        'subtitle',
        'seo',
        'content',
        'lang',
        'status',
        'public',
        'params',
        'trash',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'seo' => 'array',
        'lang' => 'array',
        'params' => 'array',
    ];



    public function scopeActive($query)
    {
        return $query->where('status', PageStatus::ACTIVE);
    }

    public function getAccessAttribute()
    {
        return ($this->public==1) ? 'public' : 'login';
    }

    public function getLinkAttribute()
    {
        return (!empty($this->menu_link)) ? $this->menu_link : route('show.page', $this->slug);
    }

    public function getTextAttribute()
    {
        return (!empty($this->menu_name)) ? $this->menu_name : $this->name;
    }
}
