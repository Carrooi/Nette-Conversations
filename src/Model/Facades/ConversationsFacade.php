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

	/** @var string */
	private $conversationClass;

	/** @var string */
	private $conversationItemClass;


	/**
	 * @param string $conversationClass
	 * @param string $conversationItemClass
	 * @param \Kdyby\Doctrine\EntityManager $em
	 */
	public function __construct($conversationClass, $conversationItemClass, EntityManager $em)
	{
		$this->em = $em;
		$this->conversationClass = $conversationClass;
		$this->conversationItemClass = $conversationItemClass;

		$this->dao = $em->getRepository('Carrooi\Conversations\Model\Entities\IConversation');
	}


	/**
	 * @return string
	 */
	public function getConversationClass()
	{
		return $this->conversationClass;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversation
	 */
	public function createNew()
	{
		$class = $this->getConversationClass();
		return new $class;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $creator
	 * @return \Carrooi\Conversations\Model\Entities\IConversation
	 */
	public function createConversation(IUser $creator)
	{
		$conversation = $this->createNew();
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
			->join($this->conversationItemClass, 'ci', Join::WITH, 'ci.conversationUserThread = cut')
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
