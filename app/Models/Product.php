<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'subcategory_id',
        'provider_name',
        'provider_reference',
        'country_code',
        'currency_code',
        'name',
        'slug',
        'description',
        'redeem_instructions',
        'terms_and_conditions',
        'logo_url',
        'featured_image',
        'is_featured',
        'is_popular',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
