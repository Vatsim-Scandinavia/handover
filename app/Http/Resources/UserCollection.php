<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            
            'id' => $this->id,
            
            'first_name' => ($this->tokenCan('full_name')) ? $this->first_name : null,
            
            'last_name' => ($this->tokenCan('full_name')) ? $this->last_name : null,
            
            'full_name' => ($this->tokenCan('full_name')) ? "{$this->first_name} {$this->last_name}" : null,
            
            'email' => ($this->tokenCan('email')) ? $this->email : null,

            'country' => ($this->tokenCan('country')) ? $this->country : null,

            'vatsim_details' => [

                'controller_rating' => [
                    'id' => ($this->tokenCan('vatsim_details')) ? $this->rating : null,
                    'short' => ($this->tokenCan('vatsim_details')) ? $this->rating_short : null,
                    'long' => ($this->tokenCan('vatsim_details')) ? $this->rating_long : null
                ],
                
                'pilot_rating' => ($this->tokenCan('vatsim_details')) ? $this->pilot_rating : null,

                'region' => ($this->tokenCan('vatsim_details')) ? $this->region : null,
                
                'division' => ($this->tokenCan('vatsim_details')) ? $this->division : null,

                'subdivision' => ($this->tokenCan('vatsim_details')) ? $this->subdivision : null,

                'active_atc' => ($this->tokenCan('vatsim_details')) ? (bool) $this->atc_active : null,

                'visiting_controller' => ($this->tokenCan('vatsim_details')) ? (bool) $this->visting_controller : null,
            ],
        ];
    }
}
