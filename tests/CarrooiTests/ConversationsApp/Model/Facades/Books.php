<?php

namespace CarrooiTests\ConversationsApp\Model\Facades;

use CarrooiTests\ConversationsApp\Model\Entities\Book;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class Books extends Object
{


	/** @var \Kdyby\Doctrine\EntityDao */
	private $dao;


	/**
	 * @param \Kdyby\Doctrine\EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->dao = $em->getRepository(Book::getClassName());
	}


	/**
	 * @return \CarrooiTests\ConversationsApp\Model\Entities\Book
	 */
	public function create()
	{
		$user = new Book;

		$this->dao->getEntityManager()->persist($user)->flush();

		return $user;
	}

}
