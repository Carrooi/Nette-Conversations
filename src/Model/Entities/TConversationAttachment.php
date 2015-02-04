<?php

namespace Carrooi\Conversations\Model\Entities;
use Doctrine\Common\Collections\ArrayCollection;

/**
 *
 * @author David Kudera
 */
trait TConversationAttachment
{


	/** @var \Doctrine\Common\Collections\ArrayCollection */
	private $conversationItems;


	private function initItems()
	{
		if (!$this->conversationItems) {
			$this->conversationItems = new ArrayCollection;
		}
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem[]
	 */
	public function getConversationItems()
	{
		$this->initItems();
		return $this->conversationItems->toArray();
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return bool
	 */
	public function hasConversationItem(IConversationItem $item)
	{
		$this->initItems();
		return $this->conversationItems->contains($item);
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return $this
	 */
	public function addConversationItem(IConversationItem $item)
	{
		$this->initItems();
		$this->conversationItems->add($item);
		return $this;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return $this
	 */
	public function removeConversationItem(IConversationItem $item)
	{
		$this->initItems();
		$this->conversationItems->removeElement($item);
		return $this;
	}

}