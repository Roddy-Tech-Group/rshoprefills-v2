<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'country_code' => $this->country_code,
            'currency_code' => $this->currency_code,
            'description' => $this->description,
            'redeem_instructions' => $this->redeem_instructions,
            'terms_and_conditions' => $this->terms_and_conditions,
            'logo_url' => $this->logo_url ? asset($this->logo_url) : null,
            'featured_image' => $this->featured_image ? asset($this->featured_image) : null,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'variants' => VariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
