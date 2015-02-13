<?php

namespace Carrooi\Conversations\Model\Entities;

use DateTime;

/**
 *
 * @author David Kudera
 */
trait TConversationItem
{


	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 * @var \DateTime
	 */
	private $createdAt;


	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 * @var \DateTime
	 */
	private $readAt;


	/**
	 * @ORM\ManyToOne(targetEntity="\Carrooi\Conversations\Model\Entities\ConversationUserThread")
	 * @ORM\JoinColumn(nullable=false)
	 * @var \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	private $conversationUserThread;


	/**
	 * @ORM\ManyToOne(targetEntity="\Carrooi\Conversations\Model\Entities\IUser")
	 * @ORM\JoinColumn(nullable=false)
	 * @var \Carrooi\Conversations\Model\Entities\IUser
	 */
	private $sender;


	/**
	 * @ORM\ManyToOne(targetEntity="\Carrooi\Conversations\Model\Entities\ConversationMessage")
	 * @var \Carrooi\Conversations\Model\Entities\ConversationMessage
	 */
	private $message;


	/**
	 * @return \DateTime
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}


	/**
	 * @return \DateTime
	 */
	public function getReadAt()
	{
		return $this->readAt;
	}


	/**
	 * @return $this
	 */
	public function setRead()
	{
		$this->readAt = new DateTime;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function isRead()
	{
		return $this->readAt !== null;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function getConversationUserThread()
	{
		return $this->conversationUserThread;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\ConversationUserThread $conversationUserThread
	 * @return $this
	 */
	public function setConversationUserThread(ConversationUserThread $conversationUserThread)
	{
		$this->conversationUserThread = $conversationUserThread;
		return $this;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IUser
	 */
	public function getSender()
	{
		return $this->sender;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function setSender(IUser $user)
	{
		$this->sender = $user;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasMessage()
	{
		return $this->message !== null;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\ConversationMessage
	 */
	public function getMessage()
	{
		return $this->message;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\ConversationMessage $message
	 * @return $this
	 */
	public function setMessage(ConversationMessage $message)
	{
		$this->message = $message;
		return $this;
	}

}
