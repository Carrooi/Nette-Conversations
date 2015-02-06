<?php

namespace Carrooi\Conversations\Model\Facades;

use Carrooi\Conversations\InvalidStateException;
use Carrooi\Conversations\Model\Entities\ConversationUserThread;
use Carrooi\Conversations\Model\Entities\IConversation;
use Carrooi\Conversations\Model\Entities\IUser;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class ConversationUserThreadsFacade extends Object
{


	/** @var \Kdyby\Doctrine\EntityManager */
	private $em;

	/** @var \Kdyby\Doctrine\EntityDao */
	private $dao;


	/**
	 * @param \Kdyby\Doctrine\EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;

		$this->dao = $em->getRepository(ConversationUserThread::getClassName());
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function isUserInConversation(IConversation $conversation, IUser $user)
	{
		return $this->findUserThreadByConversationAndUser($conversation, $user) !== null;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function addUserToConversation(IConversation $conversation, IUser $user)
	{
		$oldUserThread = $this->findUserThreadByConversationAndUser($conversation, $user, false);
		if ($oldUserThread !== null) {
			if ($oldUserThread->isAllowed()) {
				throw new InvalidStateException('User '. $user->getId(). ' is already in conversation '. $conversation->getId(). '.');
			} else {
				$oldUserThread->allow();
				$this->em->persist($oldUserThread)->flush();

				return $oldUserThread;
			}
		}

		$userThread = new ConversationUserThread;
		$userThread->setConversation($conversation);
		$userThread->setUser($user);

		$this->em->persist($userThread)->flush();

		return $userThread;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function unlinkUserFromConversation(IConversation $conversation, IUser $user)
	{
		if ($conversation->getCreator()->getId() === $user->getId()) {
			throw new InvalidStateException('Can not unlink creator from conversation '. $conversation->getId(). '.');
		}

		if (!$this->isUserInConversation($conversation, $user)) {
			throw new InvalidStateException('User '. $user->getId(). ' is not in conversation '. $conversation->getId(). '.');
		}

		$userThread = $this->findUserThreadByConversationAndUser($conversation, $user);
		$userThread->deny();

		$this->em->persist($userThread)->flush();

		return $this;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function removeUserFromConversation(IConversation $conversation, IUser $user)
	{
		if ($conversation->getCreator()->getId() === $user->getId()) {
			throw new InvalidStateException('Can not remove creator from conversation '. $conversation->getId(). '.');
		}

		if (!$this->isUserInConversation($conversation, $user)) {
			throw new InvalidStateException('User '. $user->getId(). ' is not in conversation '. $conversation->getId(). '.');
		}

		$userThread = $this->findUserThreadByConversationAndUser($conversation, $user);
		$this->em->remove($userThread)->flush();

		return $this;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @param bool $onlyAllowed
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function findUserThreadByConversationAndUser(IConversation $conversation, IUser $user, $onlyAllowed = true)
	{
		$criteria = [
			'conversation' => $conversation,
			'user' => $user,
		];

		if ($onlyAllowed) {
			$criteria['allowed'] = true;
		}

		return $this->dao->findOneBy($criteria);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function findOriginalUserThread(IConversation $conversation)
	{
		return $this->dao->findOneBy([
			'conversation' => $conversation,
		]);
	}

}
