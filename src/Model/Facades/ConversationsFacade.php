<?php

namespace Carrooi\Conversations\Model\Facades;

use Carrooi\Conversations\InvalidArgumentException;
use Carrooi\Conversations\InvalidStateException;
use Carrooi\Conversations\Model\Entities\ConversationUserThread;
use Carrooi\Conversations\Model\Entities\IConversation;
use Carrooi\Conversations\Model\Entities\IConversationAttachment;
use Carrooi\Conversations\Model\Entities\IConversationItem;
use Carrooi\Conversations\Model\Entities\IUser;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\ResultSet;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class ConversationsFacade extends Object
{


	/** @var \Kdyby\Doctrine\EntityManager */
	private $em;

	/** @var \Carrooi\Conversations\Model\Facades\AssociationsManager */
	private $associationsManager;

	/** @var \Carrooi\Conversations\Model\Facades\EntitiesProvider */
	private $entitiesProvider;

	/** @var \Carrooi\Conversations\Model\Facades\UsersFacade */
	private $users;

	/** @var \Kdyby\Doctrine\EntityDao */
	private $daoConversations;

	/** @var \Kdyby\Doctrine\EntityDao */
	private $daoUserThreads;

	/** @var \Kdyby\Doctrine\EntityDao */
	private $daoItems;


	/**
	 * @param \Kdyby\Doctrine\EntityManager $em
	 * @param \Carrooi\Conversations\Model\Facades\AssociationsManager $associationsManager
	 * @param \Carrooi\Conversations\Model\Facades\EntitiesProvider $entitiesProvider
	 * @param \Carrooi\Conversations\Model\Facades\UsersFacade $users
	 */
	public function __construct(EntityManager $em, AssociationsManager $associationsManager, EntitiesProvider $entitiesProvider, UsersFacade $users)
	{
		$this->em = $em;
		$this->associationsManager = $associationsManager;
		$this->entitiesProvider = $entitiesProvider;
		$this->users = $users;

		$this->daoConversations = $em->getRepository('Carrooi\Conversations\Model\Entities\IConversation');
		$this->daoUserThreads = $em->getRepository(ConversationUserThread::getClassName());
		$this->daoItems = $em->getRepository('Carrooi\Conversations\Model\Entities\IConversationItem');
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $creator
	 * @return \Carrooi\Conversations\Model\Entities\IConversation
	 */
	public function createConversation(IUser $creator)
	{
		$conversation = $this->entitiesProvider->createConversationEntity();
		$conversation->setCreator($creator);

		$originalThread = new ConversationUserThread;
		$originalThread->setConversation($conversation);

		$userThread = new ConversationUserThread;
		$userThread->setConversation($conversation);
		$userThread->setUser($creator);

		$this->em->persist([
			$conversation, $originalThread, $userThread,
		])->flush();

		return $conversation;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return \Kdyby\Doctrine\ResultSet|\Carrooi\Conversations\Model\Entities\IConversation[]
	 */
	public function findAllByUser(IUser $user)
	{
		$query = $this->daoConversations->createQueryBuilder('c')
			->join(ConversationUserThread::getClassName(), 'cut', Join::WITH, 'cut.conversation = c')
			->andWhere('cut.user = :user')->setParameter('user', $user)
			->andWhere('cut.allowed = TRUE')
			->getQuery();

		return new ResultSet($query);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return \Kdyby\Doctrine\ResultSet|\Carrooi\Conversations\Model\Entities\IConversation[]
	 */
	public function findAllUnreadByUser(IUser $user)
	{
		$query = $this->daoConversations->createQueryBuilder('c')
			->join('Carrooi\Conversations\Model\Entities\ConversationUserThread', 'cut', Join::WITH, 'cut.conversation = c')
			->join($this->entitiesProvider->getConversationItemClass(), 'ci', Join::WITH, 'ci.conversationUserThread = cut')
			->andWhere('cut.user = :user')->setParameter('user', $user)
			->andWhere('cut.allowed = TRUE')
			->andWhere('ci.readAt IS NULL')
			->getQuery();

		return new ResultSet($query);
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

		$this->em->persist($userThread);

		$oldItems = $this->findAllOriginalItems($conversation);

		foreach ($oldItems as $item) {
			$item = clone $item;
			$item->setConversationUserThread($userThread);

			$this->em->persist($item);
		}

		$this->em->flush();

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

		return $this->daoUserThreads->findOneBy($criteria);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @return \Carrooi\Conversations\Model\Entities\ConversationUserThread
	 */
	public function findOriginalUserThread(IConversation $conversation)
	{
		return $this->daoUserThreads->findOneBy([
			'conversation' => $conversation,
		]);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $sender
	 * @param \Carrooi\Conversations\Model\Entities\IConversationAttachment $attachment
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 */
	public function addItemToConversation(IConversation $conversation, IUser $sender, IConversationAttachment $attachment, IUser $user = null)
	{
		if ($user !== null && !$this->isUserInConversation($conversation, $user)) {
			throw new InvalidStateException('User '. $user->getId(). ' is not in conversation '. $conversation->getId(). '.');
		}

		$class = get_class($attachment);

		if (!$this->associationsManager->hasAssociation($class)) {
			throw new InvalidArgumentException('Class '. $class. ' is not registered as custom conversation attachment entity.');
		}

		if ($user) {
			$userThread = $this->findUserThreadByConversationAndUser($conversation, $user);
		} else {
			$userThread = $this->findOriginalUserThread($conversation);
		}

		$item = $this->entitiesProvider->createConversationItemEntity();
		$item->setConversationUserThread($userThread);
		$item->setSender($sender);

		if (!$user || $user->getId() === $sender->getId()) {
			$item->setRead();
		}

		$metadata = $this->em->getClassMetadata('Carrooi\Conversations\Model\Entities\IConversationItem');
		$metadata->setFieldValue($item, $this->associationsManager->getAssociation($class), $attachment);

		$attachment->addConversationItem($item);

		$this->em->transactional(function() use ($item, $attachment) {
			$this->em->persist([
				$item, $attachment
			])->flush();
		});

		return $item;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $sender
	 * @param \Carrooi\Conversations\Model\Entities\IConversationAttachment $attachment
	 * @return $this
	 */
	public function sendItem(IConversation $conversation, IUser $sender, IConversationAttachment $attachment)
	{
		$this->em->transactional(function() use ($conversation, $sender, $attachment) {
			$this->addItemToConversation($conversation, $sender, $attachment);		// store original

			$users = $this->users->findAllUsersInConversation($conversation);
			foreach ($users as $user) {
				$this->addItemToConversation($conversation, $sender, $attachment, $user);
			}
		});

		return $this;
	}


	/**
	 * @param int $id
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem
	 */
	public function findItemById($id)
	{
		return $this->daoItems->findOneBy([
			'id' => $id,
		]);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return bool
	 */
	public function isItemInConversation(IConversation $conversation, IUser $user, IConversationItem $item)
	{
		return $this->daoItems->createQueryBuilder('ci')
			->join('Carrooi\Conversations\Model\Entities\ConversationUserThread', 'cut', Join::WITH, 'cut = ci.conversationUserThread')
			->andWhere('cut.conversation = :conversation')->setParameter('conversation', $conversation)
			->andWhere('cut.user = :user')->setParameter('user', $user)
			->andWhere('ci = :item')->setParameter('item', $item)
			->getQuery()
			->getOneOrNullResult() !== null;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return $this
	 */
	public function removeItem(IConversationItem $item)
	{
		$this->em->remove($item)->flush();
		return $this;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return \Kdyby\Doctrine\ResultSet|\Carrooi\Conversations\Model\Entities\IConversationItem[]
	 */
	public function findAllItemsByConversationAndUser(IConversation $conversation, IUser $user)
	{
		$query = $this->daoItems->createQueryBuilder('ci')
			->join('ci.conversationUserThread', 'cut')
			->andWhere('cut.user = :user')->setParameter('user', $user)
			->andWhere('cut.conversation = :conversation')->setParameter('conversation', $conversation)
			->andWhere('cut.allowed = TRUE')
			->getQuery();

		return new ResultSet($query);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return \Kdyby\Doctrine\ResultSet|\Carrooi\Conversations\Model\Entities\IConversationItem[]
	 */
	public function findAllUnreadItems(IConversation $conversation, IUser $user)
	{
		$query = $this->daoItems->createQueryBuilder('ci')
			->join('ci.conversationUserThread', 'cut')
			->andWhere('cut.user = :user')->setParameter('user', $user)
			->andWhere('cut.conversation = :conversation')->setParameter('conversation', $conversation)
			->andWhere('cut.allowed = TRUE')
			->andWhere('ci.readAt IS NULL')
			->getQuery();

		return new ResultSet($query);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @return \Kdyby\Doctrine\ResultSet|\Carrooi\Conversations\Model\Entities\IConversationItem[]
	 */
	public function findAllOriginalItems(IConversation $conversation)
	{
		$query = $this->daoItems->createQueryBuilder('ci')
			->join('ci.conversationUserThread', 'cut')
			->andWhere('cut.conversation = :conversation')->setParameter('conversation', $conversation)
			->andWhere('cut.user IS NULL')
			->getQuery();

		return new ResultSet($query);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function setReadConversation(IConversation $conversation, IUser $user)
	{
		$this->em->transactional(function() use ($conversation, $user) {
			foreach ($this->findAllUnreadItems($conversation, $user) as $item) {
				$item->setRead();
				$this->em->persist($item);
			}

			$this->em->flush();
		});

		return $this;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return int
	 */
	public function countByUser(IUser $user)
	{
		return $this->findAllByUser($user)->getTotalCount();
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return int
	 */
	public function countUnreadByUser(IUser $user)
	{
		return $this->findAllUnreadByUser($user)->getTotalCount();
	}

}
