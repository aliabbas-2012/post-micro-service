<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class PhoneVerificationCode extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'phone_verification_code';
    
    
    protected $primarykey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['phone','code','created_at','updated_at'];

}
