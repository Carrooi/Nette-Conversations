<?php

namespace Carrooi\Conversations\Model\DefaultEntities\Conversation;

use Carrooi\Conversations\Model\Entities\IConversation;
use Carrooi\Conversations\Model\Entities\TConversation;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 * @ORM\Table(name="conversation")
 *
 * @author David Kudera
 */
class DefaultConversation extends BaseEntity implements IConversation
{


	use Identifier;

	use TConversation;

}
