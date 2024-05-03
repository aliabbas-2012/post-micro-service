<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Rules;

use App\Models\BlockedUser;
use Illuminate\Contracts\Validation\Rule;

/**
 * Description of IsUserBlock
 *
 * @author farazirfan
 */
class IsUserBlock implements Rule {

    private $current_user_id = null;

    public function __construct($id) {
        $this->current_user_id = $id;
    }

    public function passes($attribute, $value) {
        if (BlockedUser::where('user_id', $this->current_user_id)->where('blocked_user_id', $value)->first() != null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return 'You are blocked by this user';
    }

}
