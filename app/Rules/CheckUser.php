<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

/**
 * Description of CheckUser
 *
 * @author ali Abbas
 */
class CheckUser implements Rule {

    public function __construct() {
        
    }

    public function passes($attrName, $value) {
        die("dd");
        if (!empty($value) && $value instanceof User) {
            return true;
        }
        die("dd");
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return 'Sorry, Your session has been expired';
    }

}
