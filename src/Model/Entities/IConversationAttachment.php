<?php

namespace Carrooi\Conversations\Model\Entities;

/**
 *
 * @author David Kudera
 */
interface IConversationAttachment
{


	/**
	 * @return int
	 */
	public function getId();


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IConversationItem
	 */
	public function getConversationItems();


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return bool
	 */
	public function hasConversationItem(IConversationItem $item);


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return $this
	 */
	public function addConversationItem(IConversationItem $item);


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IConversationItem $item
	 * @return $this
	 */
	public function removeConversationItem(IConversationItem $item);

}
