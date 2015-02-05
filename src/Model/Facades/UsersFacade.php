<?php

namespace Carrooi\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Entities\IConversation;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\ResultSet;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class UsersFacade extends Object
{


	/** @var \Kdyby\Doctrine\EntityDao */
	private $dao;


	/**
	 * @param \Kdyby\Doctrine\EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->dao = $em->getRepository('Carrooi\Conversations\Model\Entities\IUser');
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @return \Kdyby\Doctrine\ResultSet|\Carrooi\Conversations\Model\Entities\IUser[]
	 */
	public function findAllUsersInConversation(IConversation $conversation)
	{
		$query = $this->dao->createQueryBuilder('u')
			->join('Carrooi\Conversations\Model\Entities\ConversationUserThread', 'cut', Join::WITH, 'cut.user = u')
			->andWhere('cut.conversation = :conversation')->setParameter('conversation', $conversation)
			->andWhere('cut.allowed = TRUE')
			->getQuery();

		return new ResultSet($query);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversation $conversation
	 * @return int
	 */
	public function countUsersInConversation(IConversation $conversation)
	{
		return $this->findAllUsersInConversation($conversation)->getTotalCount();
	}

}
