<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;

/**
 * Description of CheckBoxes
 *
 * @author ali Abbas
 */
class CheckBoxes implements Rule {

    private $dbBoxesLength = null;

    public function __construct($dbBoxesLength) {
        $this->dbBoxesLength = $dbBoxesLength;
    }

    public function passes($attrName, $value) {
        return count($value) == $this->dbBoxesLength;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return 'Invalid Boxes';
    }

}
