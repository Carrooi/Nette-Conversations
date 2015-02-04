<?php

namespace CarrooiTests\ConversationsApp\Model\Entities;

use Carrooi\Conversations\Model\Entities\IConversationAttachment;
use Carrooi\Conversations\Model\Entities\TConversationAttachment;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 *
 * @author David Kudera
 */
class Book extends BaseEntity implements IConversationAttachment
{


	use Identifier;

	use TConversationAttachment;

}
