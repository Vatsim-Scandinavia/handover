<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'tags'       => $this->tags->pluck('tag')->values()->toArray(),
            'attributes' => $this->attributeValues
                ->mapWithKeys(fn ($av) => [$av->definition->key => $av->value])
                ->toArray(),
        ];
    }
}
