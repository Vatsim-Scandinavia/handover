<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function __construct($resource, private bool $direct = true)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'direct'     => $this->direct,
            'tags'       => $this->tags->pluck('tag')->values()->toArray(),
            'attributes' => $this->attributeValues
                ->mapWithKeys(fn ($av) => [$av->definition->key => $av->value])
                ->toArray(),
        ];
    }
}
