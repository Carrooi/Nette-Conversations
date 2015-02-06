<?php

namespace Carrooi\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Entities\ConversationUserThread;
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

	/** @var \Kdyby\Doctrine\EntityDao */
	private $dao;

	/** @var \Carrooi\Conversations\Model\Facades\EntitiesProvider */
	private $entitiesProvider;


	/**
	 * @param \Kdyby\Doctrine\EntityManager $em
	 * @param \Carrooi\Conversations\Model\Facades\EntitiesProvider $entitiesProvider
	 */
	public function __construct(EntityManager $em, EntitiesProvider $entitiesProvider)
	{
		$this->em = $em;
		$this->entitiesProvider = $entitiesProvider;

		$this->dao = $em->getRepository('Carrooi\Conversations\Model\Entities\IConversation');
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
		$query = $this->dao->createQueryBuilder('c')
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
		$query = $this->dao->createQueryBuilder('c')
			->join('Carrooi\Conversations\Model\Entities\ConversationUserThread', 'cut', Join::WITH, 'cut.conversation = c')
			->join($this->entitiesProvider->getConversationItemClass(), 'ci', Join::WITH, 'ci.conversationUserThread = cut')
			->andWhere('cut.user = :user')->setParameter('user', $user)
			->andWhere('cut.allowed = TRUE')
			->andWhere('ci.readAt IS NULL')
			->getQuery();

		return new ResultSet($query);
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
