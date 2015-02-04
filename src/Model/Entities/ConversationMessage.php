<?php

namespace Carrooi\Conversations\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 *
 * @author David Kudera
 */
class ConversationMessage extends BaseEntity implements IConversationAttachment
{


	use Identifier;

	use TConversationAttachment;


	/**
	 * @ORM\Column(type="text", nullable=false)
	 * @var string
	 */
	private $text;


	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}


	/**
	 * @param string $text
	 * @return $this
	 */
	public function setText($text)
	{
		$this->text = $text;
		return $this;
	}

}
