<?php
/**
 * @link      http://github.com/zetta-repo/tss-authentication for the canonical source repository
 * @copyright Copyright (c) 2016 Zetta Code
 */

namespace TSS\Authentication\Form;

use Doctrine\ORM\EntityManagerInterface;
use TSS\Authentication\Filter\SignupFilter;
use Zend\Form\Form;
use Zend\Hydrator\ClassMethods;

class SignupForm extends Form
{

    public function __construct(EntityManagerInterface $em, $name = 'signup', $options = null)
    {
        parent::__construct($name, $options);
        $this->setAttribute('method', 'post');
        $this->setAttribute('role', 'form');
        $this->setHydrator(new ClassMethods(false));
        $this->setInputFilter(new SignupFilter($em, $options));

        $this->add([
            'name' => 'id',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'username',
            'type' => 'text',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => _('Username'),
            ],
            'options' => [
                'label' => 'Username',
                'label_attributes' => ['class' => 'control-label'],
                'div' => ['class' => 'form-group', 'class_error' => 'has-error'],
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'text',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => _('Email'),
            ],
            'options' => [
                'label' => 'Email',
                'label_attributes' => ['class' => 'control-label'],
                'div' => ['class' => 'form-group', 'class_error' => 'has-error'],
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => 'password',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => _('Password'),
            ],
            'options' => [
                'label' => 'Password',
                'label_attributes' => ['class' => 'control-label'],
                'div' => ['class' => 'form-group', 'class_error' => 'has-error'],
            ],
        ]);

        $this->add([
            'name' => 'password-confirm',
            'type' => 'password',
            'attributes' => [
                'class' => 'form-control',
                'placeholder' => _('Confirm Password'),
            ],
            'options' => [
                'label' => _('Confirm Password'),
                'label_attributes' => ['class' => 'control-label'],
                'div' => ['class' => 'form-group', 'class_error' => 'has-error'],
            ],
        ]);

        $this->add([
            'name' => 'accepted-terms',
            'type' => 'checkbox',
            'options' => [
                'label' => _('I have read and accepted the terms of use.'),
                'label_options' => ['always_wrap' => true],
                'div' => ['class' => 'checkbox', 'class_error' => 'has-error'],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'class' => 'btn btn-lg btn-block btn-primary',
                'value' => _('Sign me up'),
                'id' => 'submit',
            ],
        ]);
    }
}
