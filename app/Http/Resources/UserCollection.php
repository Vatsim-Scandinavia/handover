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
        ];
    }
}
