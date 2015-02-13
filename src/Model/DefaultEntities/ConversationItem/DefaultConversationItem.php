<?php

namespace Carrooi\Conversations\Model\DefaultEntities\ConversationItem;

use Carrooi\Conversations\Model\Entities\IConversationItem;
use Carrooi\Conversations\Model\Entities\TConversationItem;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 * @ORM\Table(name="conversation_item")
 *
 * @author David Kudera
 */
class DefaultConversationItem extends BaseEntity implements IConversationItem
{


	use Identifier;

	use TConversationItem;

}
