<?php

namespace Carrooi\Conversations\Model\Events;

use Carrooi\Conversations\InvalidStateException;
use Carrooi\Conversations\Model\Facades\AssociationsManager;
use Carrooi\Conversations\Model\Facades\EntitiesProvider;
use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Kdyby\Events\Subscriber;
use Nette\Object;

/**
 *
 * @author David Kudera
 */
class ConversationsRelationsSubscriber extends Object implements Subscriber
{


	const ASSOCIATION_FIELD_NAME = 'conversationItems';


	/** @var \Carrooi\Conversations\Model\Facades\EntitiesProvider */
	private $entitiesProvider;

	/** @var \Carrooi\Conversations\Model\Facades\AssociationsManager */
	private $associations;


	/**
	 * @param \Carrooi\Conversations\Model\Facades\EntitiesProvider $entitiesProvider
	 * @param \Carrooi\Conversations\Model\Facades\AssociationsManager $associations
	 */
	public function __construct(EntitiesProvider $entitiesProvider, AssociationsManager $associations)
	{
		$this->entitiesProvider = $entitiesProvider;
		$this->associations = $associations;
	}


	/**
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return [
			Events::loadClassMetadata => 'loadClassMetadata',
			Events::prePersist => 'prePersist',
		];
	}


	/**
	 * @param \Doctrine\ORM\Event\LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$metadata = $eventArgs->getClassMetadata();				/** @var \Kdyby\Doctrine\Mapping\ClassMetadata $metadata */
		$class = $metadata->getName();
		$implements = class_implements($class);

		if (
			(in_array('Carrooi\Conversations\Model\Entities\IConversation', $implements) && $class !== $this->entitiesProvider->getConversationClass()) ||
			(in_array('Carrooi\Conversations\Model\Entities\IConversationItem', $implements) && $class !== $this->entitiesProvider->getConversationItemClass())
		) {
			$metadata->setPrimaryTable([
				'name' => $metadata->getTableName(). '_unused',		// don't know any other way how to create testing schema without temporary removing some files
			]);
			return;
		}

		if ($class === $this->entitiesProvider->getConversationItemClass()) {
			foreach ($this->associations->getAssociations() as $assocClass => $field) {
				if (!$metadata->hasAssociation($field)) {
					throw new InvalidStateException('Missing manyToOne association at '. $class. '::$'. $field. '.');
				}

				$metadata->setAssociationOverride($field, [
					'type' => ClassMetadataInfo::ONE_TO_MANY,
					'targetEntity' => $assocClass,
					'fieldName' => $field,
					'inversedBy' => self::ASSOCIATION_FIELD_NAME,
					'joinColumn' => [
						'nullable' => true,
					],
				]);
			}

		} elseif (in_array('Carrooi\Conversations\Model\Entities\IConversationAttachment', $implements) && $this->associations->hasAssociation($class)) {
			$metadata->mapOneToMany([
				'targetEntity' => 'Carrooi\Conversations\Model\Entities\IConversationItem',
				'fieldName' => self::ASSOCIATION_FIELD_NAME,
				'mappedBy' => $this->associations->getAssociation($class),
			]);
		}
	}


	/**
	 * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
	 */
	public function prePersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$implements = class_implements($entity);

		if (
			(in_array('Carrooi\Conversations\Model\Entities\IConversation', $implements)) ||
			(in_array('Carrooi\Conversations\Model\Entities\IConversationItem', $implements))
		)
		{
			$metadata = $eventArgs->getEntityManager()->getClassMetadata(get_class($entity));

			if ($metadata->getFieldValue($entity, 'createdAt') === null) {
				$metadata->setFieldValue($entity, 'createdAt', new DateTime);
			}
		}
	}

}
