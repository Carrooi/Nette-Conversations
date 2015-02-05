<?php

namespace Carrooi\Conversations\DI;

use Carrooi\Conversations\InvalidArgumentException;
use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Doctrine\DI\ITargetEntityProvider;
use Kdyby\Events\DI\EventsExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;

/**
 *
 * @author David Kudera
 */
class ConversationsExtension extends CompilerExtension implements IEntityProvider, ITargetEntityProvider
{


	/** @var array */
	private $defaultAttachments = [
		'Carrooi\Conversations\Model\Entities\ConversationMessage' => 'message',
	];


	/** @var array */
	private $defaults = [
		'userClass' => null,
		'conversationClass' => 'Carrooi\Conversations\Model\DefaultEntities\Conversation\DefaultConversation',
		'conversationItemClass' => 'Carrooi\Conversations\Model\DefaultEntities\ConversationItem\DefaultConversationItem',
		'attachments' => [],
	];


	/** @var string */
	private $userClass;

	/** @var string */
	private $conversationClass;

	/** @var string */
	private $conversationItemClass;


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		if (!$config['userClass']) {
			throw new InvalidArgumentException('Please, set user class name in conversations configuration.');
		}

		$this->userClass = $config['userClass'];
		$this->conversationClass = $config['conversationClass'];
		$this->conversationItemClass = $config['conversationItemClass'];

		foreach ($this->compiler->getExtensions('Carrooi\Conversations\DI\IConversationsAttachmentsProvider') as $extension) {
			/** @var \Carrooi\Conversations\DI\IConversationsAttachmentsProvider $extension */

			$config['attachments'] = Helpers::merge($config['attachments'], $extension->getConversationsAttachments());
		}

		if (!empty($config['attachments']) && $config['conversationItemClass'] === 'Carrooi\Conversations\Model\DefaultEntities\Conversation\DefaultConversationItem') {
			throw new InvalidArgumentException('Please, before you use any custom attachments at conversations module, extend DefaultConversationItem class.');
		}

		$config['attachments'] = Helpers::merge($config['attachments'], $this->defaultAttachments);

		$builder->addDefinition($this->prefix('facade.entities'))
			->setClass('Carrooi\Conversations\Model\Facades\EntitiesProvider')
			->setArguments([$this->conversationClass, $this->conversationItemClass]);

		$builder->addDefinition($this->prefix('facade.messages'))
			->setClass('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');

		$builder->addDefinition($this->prefix('facade.conversations'))
			->setClass('Carrooi\Conversations\Model\Facades\ConversationsFacade');

		$builder->addDefinition($this->prefix('events.relations'))
			->setClass('Carrooi\Conversations\Model\Events\ConversationsRelationsSubscriber')
			->addTag(EventsExtension::TAG_SUBSCRIBER);

		$associations = $builder->addDefinition($this->prefix('facade.associations'))
			->setClass('Carrooi\Conversations\Model\Facades\AssociationsManager');

		foreach ($config['attachments'] as $class => $field) {
			$associations->addSetup('addAssociation', [$class, $field]);
		}
	}


	/**
	 * @return array
	 */
	public function getEntityMappings()
	{
		$mappings = [
			'Carrooi\Conversations\Model\Entities' => __DIR__. '/../Model/Entities'
		];

		if ($this->conversationClass === 'Carrooi\Conversations\Model\DefaultEntities\Conversation\DefaultConversation') {
			$mappings['Carrooi\Conversations\Model\DefaultEntities\Conversation'] = __DIR__. '/../Model/DefaultEntities/Conversation';
		}

		if ($this->conversationItemClass === 'Carrooi\Conversations\Model\DefaultEntities\ConversationItem\DefaultConversationItem') {
			$mappings['Carrooi\Conversations\Model\DefaultEntities\ConversationItem'] = __DIR__. '/../Model/DefaultEntities/ConversationItem';
		}

		return $mappings;
	}


	/**
	 * @return array
	 */
	public function getTargetEntityMappings()
	{
		return [
			'Carrooi\Conversations\Model\Entities\IUser' => $this->userClass,
			'Carrooi\Conversations\Model\Entities\IConversation' => $this->conversationClass,
			'Carrooi\Conversations\Model\Entities\IConversationItem' => $this->conversationItemClass,
		];
	}
}
