<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nrc extends Model
{
    protected $fillable = ['state_code', 'township_code', 'type', 'number'];
    
    public static function rules()
    {
         return [
        'state_code' => [
            'required', 
            'string', 
            'size:2',
            Rule::exists('nrc_states', 'code')
        ],
        'township_code' => [
            'required',
            'string',
            'size:3',
            Rule::exists('nrc_townships', 'code')->where('state_code', request('state_code'))
        ],
        'type' => [
            'required',
            Rule::in(['N', 'P', 'E'])
        ],
        'number' => 'required|numeric|digits:6'
    ];
    }
    
    public function getFullNrcAttribute()
    {
        return "{$this->state_code}/{$this->township_code}({$this->type}){$this->number}";
    }
}
