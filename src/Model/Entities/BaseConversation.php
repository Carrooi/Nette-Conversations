<?php

namespace Carrooi\Conversations\Model\Entities;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 *
 * @ORM\MappedSuperclass
 *
 * @author David Kudera
 */
abstract class BaseConversation extends BaseEntity implements IConversation
{


	use Identifier;


	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 * @var \DateTime
	 */
	private $createdAt;


	/**
	 * @ORM\ManyToOne(targetEntity="\Carrooi\Conversations\Model\Entities\IUser")
	 * @ORM\JoinColumn(nullable=false)
	 * @var \Carrooi\Conversations\Model\Entities\IUser
	 */
	private $creator;


	/**
	 * @return \DateTime
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}


	/**
	 * @return \Carrooi\Conversations\Model\Entities\IUser
	 */
	public function getCreator()
	{
		return $this->creator;
	}


	/**
	 * @param \Carrooi\Conversations\Model\Entities\IUser $user
	 * @return $this
	 */
	public function setCreator(IUser $user)
	{
		$this->creator = $user;
		return $this;
	}

}
