<?php

namespace CarrooiTests\ConversationsApp\Model\Entities;

use Carrooi\Conversations\Model\Entities\IConversation;
use Carrooi\Conversations\Model\Entities\TConversation;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 *
 * @author David Kudera
 */
class Conversation extends BaseEntity implements IConversation
{


	use Identifier;

	use TConversation;

}
