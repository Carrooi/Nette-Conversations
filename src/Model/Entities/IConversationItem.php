<?php

namespace Carrooi\Conversations\Model\Entities;

/**
 *
 * @author David Kudera
 */
interface IConversationItem
{


	/**
	 * @return int
	 */
	public function getId();


	/**
	 * @return \DateTime
	 */
	public function getCreatedAt();


	/**
	 * @return \DateTime
	 */
	public function getReadAt();


	/**
	 * @return $this
	 */
	public function setRead();


	/**
	 * @return bool
	 */
	public function isRead();


	/**
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function getConversationUserThread();


	/**
	 * @param \Carrooi\Conversations\Model\Entities\ConversationUserThread $conversationUserThread
	 * @return $this
	 */
	public function setConversationUserThread(ConversationUserThread $conversationUserThread);


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IUser
	 */
	public function getSender();


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function setSender(IUser $user);


	/**
	 * @return bool
	 */
	public function hasMessage();


	/**
	 * @return \Carrooi\Conversations\Model\Entities\ConversationMessage
	 */
	public function getMessage();


	/**
	 * @param \Carrooi\Conversations\Model\Entities\ConversationMessage $message
	 * @return $this
	 */
	public function setMessage(ConversationMessage $message);

}
