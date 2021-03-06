<?php

namespace Carrooi\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Entities\ConversationMessage;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class ConversationMessagesFacade extends Object
{


	/** @var \Kdyby\Doctrine\EntityManager */
	private $em;


	/**
	 * @param \Kdyby\Doctrine\EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}


	/**
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	protected function getEntityManager()
	{
		return $this->em;
	}


	/**
	 * @param string $text
	 * @return \Carrooi\Conversations\Model\Entities\ConversationMessage
	 */
	public function create($text)
	{
		$message = new ConversationMessage;
		$message->setText($text);

		$this->getEntityManager()->persist($message)->flush($message);

		return $message;
	}

}
