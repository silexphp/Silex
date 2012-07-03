<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silex\Tests\Provider;

use DateTime;
use JMS\SerializerBundle\Annotation\Type;

/**
 * This class is used by the JMSSerializerServiceProviderTest.
 *
 * @author Marijn Huizendveld <marijn@pink-tie.com>
 */
class SerializableUser
{
    /**
     * @Type("integer")
     */
    private $id;

    /**
     * @Type("string")
     */
    private $name;

    /**
     * @Type("DateTime")
     */
    private $created;

    public function __construct($id, $name, DateTime $created)
    {
        $this->id = $id;
        $this->name = $name;
        $this->created = $created;
    }
}
