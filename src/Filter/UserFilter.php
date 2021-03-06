<?php
/**
 * @link      http://github.com/zetta-repo/tss-authentication for the canonical source repository
 * @copyright Copyright (c) 2016 Zetta Code
 */

namespace TSS\Authentication\Filter;

use Doctrine\ORM\EntityManagerInterface;
use TSS\Authentication\Filter\Fieldset\UserFiledsetFilter;
use Zend\InputFilter\InputFilter;

class UserFilter extends InputFilter
{
    /**
     * UserFilter constructor.
     * @param EntityManagerInterface $em
     * @param null $options
     */
    public function __construct(EntityManagerInterface $em, $options = null)
    {
        $this->add(new UserFiledsetFilter($em, $options), 'user');
    }
}
