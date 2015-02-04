<?php

namespace Carrooi\Conversations\Model\Facades;

use Nette\Object;

/**
 *
 * @author David Kudera
 */
class EntitiesProvider extends Object
{


	/** @var string */
	private $conversationClass;

	/** @var string */
	private $conversationItemClass;


	/**
	 * @param string $conversationClass
	 * @param string $conversationItemClass
	 */
	public function __construct($conversationClass, $conversationItemClass)
	{
		$this->conversationClass = $this->parseClass($conversationClass);
		$this->conversationItemClass = $this->parseClass($conversationItemClass);
	}


	/**
	 * @param string $class
	 * @return string
	 */
	private function parseClass($class)
	{
		return ltrim($class, '\\');
	}


	/**
	 * @return string
	 */
	public function getConversationClass()
	{
		return $this->conversationClass;
	}


	/**
	 * @return string
	 */
	public function getConversationItemClass()
	{
		return $this->conversationItemClass;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversation
	 */
	public function createConversationEntity()
	{
		$class = $this->getConversationClass();
		return new $class;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem
	 */
	public function createConversationItemEntity()
	{
		$class = $this->getConversationItemClass();
		return new $class;
	}

}
