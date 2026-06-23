<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'category'   => new CategoryResource($this->whenLoaded('category')),
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'gender'     => $this->gender,
            'email'      => $this->email,
            'tel'        => $this->tel,
            'address'    => $this->address,
            'building'   => $this->building,
            'detail'     => $this->detail,
            'tags'       => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}