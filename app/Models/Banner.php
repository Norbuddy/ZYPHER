<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'button_text',
        'button_link',
        'title_font',
        'subtitle_font',
        'button_font',
        'title_color',
        'subtitle_color',
        'button_text_color',
        'button_background',
        'button_gradient_color',
        'button_style',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}