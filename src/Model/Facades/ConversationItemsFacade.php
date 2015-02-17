<?php

namespace Carrooi\Conversations\Model\Facades;

use Carrooi\Conversations\InvalidArgumentException;
use Carrooi\Conversations\InvalidStateException;
use Carrooi\Conversations\Model\Entities\ConversationUserThread;
use Carrooi\Conversations\Model\Entities\IConversation;
use Carrooi\Conversations\Model\Entities\IConversationAttachment;
use Carrooi\Conversations\Model\Entities\IConversationItem;
use Carrooi\Conversations\Model\Entities\IUser;
use DateTime;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\ResultSet;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class ConversationItemsFacade extends Object
{


	/** @var \Kdyby\Doctrine\EntityManager */
	private $em;

	/** @var \Kdyby\Doctrine\EntityDao */
	private $dao;

	/** @var \Carrooi\Conversations\Model\Facades\AssociationsManager */
	private $associationsManager;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade */
	private $userThreads;

	/** @var \Carrooi\Conversations\Model\Facades\UsersFacade */
	private $users;

	/** @var string */
	private $class;


	/**
	 * @param string $class
	 * @param \Kdyby\Doctrine\EntityManager $em
	 * @param \Carrooi\Conversations\Model\Facades\AssociationsManager $associationsManager
	 * @param \Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade $userThreads
	 * @param \Carrooi\Conversations\Model\Facades\UsersFacade $users
	 */
	public function __construct($class, EntityManager $em, AssociationsManager $associationsManager, ConversationUserThreadsFacade $userThreads, UsersFacade $users)
	{
		$this->em = $em;
		$this->associationsManager = $associationsManager;
		$this->userThreads = $userThreads;
		$this->users = $users;
		$this->class = $class;

		$this->dao = $em->getRepository('Carrooi\Conversations\Model\Entities\IConversationItem');
	}


	/**
	 * @return string
	 */
	public function getClass()
	{
		return $this->class;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem
	 */
	public function createNew()
	{
		$class = $this->getClass();
		return new $class;
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
		if ($user !== null && !$this->userThreads->isUserInConversation($conversation, $user)) {
			throw new InvalidStateException('User '. $user->getId(). ' is not in conversation '. $conversation->getId(). '.');
		}

		$class = get_class($attachment);

		if (!$this->associationsManager->hasAssociation($class)) {
			throw new InvalidArgumentException('Class '. $class. ' is not registered as custom conversation attachment entity.');
		}

		if ($user) {
			$userThread = $this->userThreads->findUserThreadByConversationAndUser($conversation, $user);
		} else {
			$userThread = $this->userThreads->findOriginalUserThread($conversation);
		}

		$item = $this->createNew();
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
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @param \Carrooi\Conversations\Model\Entities\ConversationUserThread $userThread
	 * @return $this
	 */
	public function cloneOriginalItems(IConversation $conversation, ConversationUserThread $userThread)
	{
		$oldItems = $this->findAllOriginalItems($conversation);

		foreach ($oldItems as $item) {
			$item = clone $item;
			$item->setConversationUserThread($userThread);

			$this->em->persist($item);
		}

		$this->em->flush();

		return $this;
	}


	/**
	 * @param int $id
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem
	 */
	public function findItemById($id)
	{
		return $this->dao->findOneBy([
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
		return $this->dao->createQueryBuilder('ci')
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
		$query = $this->dao->createQueryBuilder('ci')
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
		$query = $this->dao->createQueryBuilder('ci')
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
		$query = $this->dao->createQueryBuilder('ci')
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
		$where = $this->dao->createQueryBuilder()
			->select('cut')->from('Carrooi\Conversations\Model\Entities\ConversationUserThread', 'cut')
			->andWhere('cut.user = :user')
			->andWhere('cut.conversation = :conversation')
			->andWhere('cut.allowed = TRUE');

		$qb = $this->dao->createQueryBuilder();

		$qb->update($this->getClass(), 'i')
			->set('i.readAt', ':now')
			->andWhere('i.readAt IS NULL')
			->andWhere($qb->expr()->in('i.conversationUserThread', $where->getDQL()))
			->setParameter('now', new DateTime)
			->setParameter('user', $user)
			->setParameter('conversation', $conversation);

		$qb->getQuery()->execute();

		return $this;
	}

}
