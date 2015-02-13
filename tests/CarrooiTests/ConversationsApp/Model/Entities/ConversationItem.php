<?php

namespace CarrooiTests\ConversationsApp\Model\Entities;

use Carrooi\Conversations\Model\Entities\IConversationItem;
use Carrooi\Conversations\Model\Entities\TConversationItem;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\Entity
 *
 * @author David Kudera
 */
class ConversationItem extends BaseEntity implements IConversationItem
{


	use Identifier;

	use TConversationItem;


	/**
	 * @ORM\ManyToOne(targetEntity="\CarrooiTests\ConversationsApp\Model\Entities\Book")
	 * @var \CarrooiTests\ConversationsApp\Model\Entities\Book
	 */
	private $book;


	/**
	 * @return bool
	 */
	public function hasBook()
	{
		return $this->book !== null;
	}


	/**
	 * @return \CarrooiTests\ConversationsApp\Model\Entities\Book
	 */
	public function getBook()
	{
		return $this->book;
	}


	/**
	 * @param \CarrooiTests\ConversationsApp\Model\Entities\Book $book
	 * @return $this
	 */
	public function setBook(Book $book)
	{
		$this->book = $book;
		return $this;
	}

}
