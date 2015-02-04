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
class ConversationUserThread extends BaseEntity
{


	use Identifier;


	/**
	 * @ORM\ManyToOne(targetEntity="\Carrooi\Conversations\Model\Entities\IConversation")
	 * @ORM\JoinColumn(nullable=false)
	 * @var \Carrooi\Conversations\Model\Entities\IConversation
	 */
	private $conversation;


	/**
	 * @ORM\ManyToOne(targetEntity="\Carrooi\Conversations\Model\Entities\IUser")
	 * @ORM\JoinColumn(nullable=true)
	 * @var \Carrooi\Conversations\Model\Entities\IUser
	 */
	private $user;


	/**
	 * @ORM\Column(type="boolean", nullable=false)
	 * @var bool
	 */
	private $allowed = true;


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversation
	 */
	public function getConversation()
	{
		return $this->conversation;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @return $this
	 */
	public function setConversation(IConversation $conversation)
	{
		$this->conversation = $conversation;
		return $this;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IUser
	 */
	public function getUser()
	{
		return $this->user;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function setUser(IUser $user)
	{
		$this->user = $user;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function isAllowed()
	{
		return $this->allowed === true;
	}


	/**
	 * @return $this
	 */
	public function allow()
	{
		$this->allowed = true;
		return $this;
	}


	/**
	 * @return $this
	 */
	public function deny()
	{
		$this->allowed = false;
		return $this;
	}

}
