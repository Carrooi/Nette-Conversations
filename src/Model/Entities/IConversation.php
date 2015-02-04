<?php

namespace Carrooi\Conversations\Model\Entities;

/**
 *
 * @author David Kudera
 */
interface IConversation
{


	/**
	 * @return int
	 */
	public function getId();


	/**
	 * @return \DateTime
	 */
	public function getCreatedAt();


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IUser
	 */
	public function getCreator();


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function setCreator(IUser $user);

}
