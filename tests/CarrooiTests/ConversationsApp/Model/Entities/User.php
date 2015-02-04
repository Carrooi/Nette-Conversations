<?php

namespace CarrooiTests\ConversationsApp\Model\Entities;

use Carrooi\Conversations\Model\Entities\IUser;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 *
 * @author David Kudera
 */
class User extends BaseEntity implements IUser
{


	use Identifier;

}
