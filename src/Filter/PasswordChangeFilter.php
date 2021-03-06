<?php
/**
 * @link      http://github.com/zetta-repo/tss-authentication for the canonical source repository
 * @copyright Copyright (c) 2016 Zetta Code
 */

namespace TSS\Authentication\Filter;

use Zend\InputFilter\InputFilter;

class PasswordChangeFilter extends InputFilter
{
    /**
     * PasswordChangeFilter constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->add([
            'name' => 'password-old',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
        ]);

        $this->add([
            'name' => 'password-new',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 5,
                        'max' => 16,
                    ],
                ],
            ],
        ]);

        $this->add([
            'name' => 'password-new-confirm',
            'required' => true,
            'filters' => [
                ['name' => 'StripTags'],
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'password-new',
                    ],
                ],
            ],
        ]);
    }
}
