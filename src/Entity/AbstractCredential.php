<?php
/**
 * @link      http://github.com/zetta-repo/tss-authentication for the canonical source repository
 * @copyright Copyright (c) 2016 Zetta Code
 */

namespace TSS\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;
use TSS\DoctrineUtil\Entity\AbstractEntity;

/**
 * Class AbstractCredential
 * @package TSS\Authentication\Entity
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractCredential extends AbstractEntity implements CredentialInterface
{
    const TYPE_PASSWORD = 1;
    const TYPE_FACEBOOK = 2;
    const TYPE_API_TOKEN = 3;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(type="smallint")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $value;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $active;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }
}
