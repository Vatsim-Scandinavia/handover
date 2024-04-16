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
    public function toArray($request) {
    
        return [
            
            'cid' => $this->id,

            'personal' => [
                'name_first' => ($this->tokenCan('full_name')) ? $this->first_name : null,
            
                'name_last' => ($this->tokenCan('full_name')) ? $this->last_name : null,
                
                'name_full' => ($this->tokenCan('full_name')) ? "{$this->first_name} {$this->last_name}" : null,

                'name_full_cid' => ($this->tokenCan('full_name')) ? "{$this->first_name} {$this->last_name} ({$this->id})" : null,
                
                'email' => ($this->tokenCan('email')) ? $this->email : null,

                'country' => [
                    'id' => ($this->tokenCan('country')) ? $this->country : null,
                    'name' => 'N/A',
                ],
                
            ],

            'vatsim' => [

                'rating' => [
                    'id' => ($this->tokenCan('vatsim_details')) ? $this->rating : null,
                    'short' => ($this->tokenCan('vatsim_details')) ? $this->rating_short : null,
                    'long' => ($this->tokenCan('vatsim_details')) ? $this->rating_long : null
                ],

                'pilotrating' => [
                    'id' => ($this->tokenCan('vatsim_details')) ? $this->pilot_rating : null,
                    'short' => 'N/A',
                    'long' => 'N/A',
                ],
                
                'region' => [
                    'id' => ($this->tokenCan('vatsim_details')) ? $this->region : null,
                    'name' => 'N/A',
                ],

                'division' => [
                    'id' => ($this->tokenCan('vatsim_details')) ? $this->division : null,
                    'name' => 'N/A',
                ],

                'subdivision' => [
                    'id' => ($this->tokenCan('vatsim_details')) ? $this->subdivision : null,
                    'name' => 'N/A',
                ],

            ],

            'oauth' => [
                // We're not entirely sure what the purpose of this field is, at least
                // not for the time being.
                //
                // In Handover, a client making a request to the /api/user endpoint
                // isn't going to receive a valid response containing this field
                // *UNLESS* the token *IS* valid. Thus, if you're seeing this, you're
                // already on the other side of the airtight hatchway.
                //
                // Oh, and it's apparently meant to be a string.
                'token_valid' => 'true',
            ],
        ];
    }
}
