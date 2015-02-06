<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationsFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationsFacadeTest
 * @author David Kudera
 */

namespace CarrooiTests\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Entities\IConversation;
use CarrooiTests\Conversations\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ConversationsFacadeTest extends TestCase
{


	/** @var \Carrooi\Conversations\Model\Facades\ConversationsFacade */
	private $conversations;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationItemsFacade */
	private $items;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade */
	private $userThreads;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $appUsers;


	/**
	 * @param string $customConfig
	 * @return \Nette\DI\Container
	 */
	protected function createContainer($customConfig = null)
	{
		$container = parent::createContainer($customConfig);

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
		$this->items = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationItemsFacade');
		$this->messages = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');
		$this->userThreads = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade');
		$this->appUsers = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Users');

		return $container;
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->conversations = $this->appUsers = null;
	}


	public function testCreateConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::notSame(null, $conversation->getId());
		Assert::notSame(null, $conversation->getCreatedAt());
		Assert::same($creator->getId(), $conversation->getCreator()->getId());
	}


	public function testFindAllByUser()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();

		$this->conversations->createConversation($creator);
		$this->conversations->createConversation($creator);
		$this->conversations->createConversation($creator);

		$user = $this->appUsers->create();

		$conversation = $this->conversations->createConversation($this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $user);

		$conversation = $this->conversations->createConversation($this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->userThreads->unlinkUserFromConversation($conversation, $user);

		Assert::count(3, $this->conversations->findAllByUser($creator));
		Assert::count(1, $this->conversations->findAllByUser($user));
	}


	public function testCountByUser()
	{
		$this->createContainer();

		$user = $this->appUsers->create();

		$this->conversations->createConversation($user);
		$this->conversations->createConversation($user);
		$this->conversations->createConversation($user);
		$this->conversations->createConversation($user);

		$conversation = $this->conversations->createConversation($this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $user);
		$this->userThreads->unlinkUserFromConversation($conversation, $user);

		Assert::same(4, $this->conversations->countByUser($user));
	}


	public function testFindAllUnreadByUser()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$user = $this->appUsers->create();

		$conversation1 = $this->conversations->createConversation($creator);
		$this->userThreads->addUserToConversation($conversation1, $user);

		$this->items->sendItem($conversation1, $creator, $this->messages->create('lorem'));
		$this->items->sendItem($conversation1, $creator, $this->messages->create('lorem'));

		$this->items->sendItem($conversation1, $user, $this->messages->create('lorem'));

		$conversation2 = $this->conversations->createConversation($creator);
		$this->userThreads->addUserToConversation($conversation2, $user);

		$this->items->sendItem($conversation2, $creator, $this->messages->create('lorem'));

		$this->items->sendItem($conversation2, $user, $this->messages->create('lorem'));

		$conversation3 = $this->conversations->createConversation($creator);
		$this->userThreads->addUserToConversation($conversation3, $user);

		$this->items->sendItem($conversation3, $user, $this->messages->create('lorem'));

		$conversations = $this->conversations->findAllUnreadByUser($user)->toArray();
		/** @var \Carrooi\Conversations\Model\Entities\IConversation[] $conversations */

		$ids = array_map(function(IConversation $conversation) {
			return $conversation->getId();
		}, $conversations);

		Assert::count(2, $conversations);
		Assert::contains($conversation1->getId(), $ids);
		Assert::contains($conversation2->getId(), $ids);

		$conversations = $this->conversations->findAllUnreadByUser($creator)->toArray();
		/** @var \Carrooi\Conversations\Model\Entities\IConversation[] $conversations */

		$ids = array_map(function(IConversation $conversation) {
			return $conversation->getId();
		}, $conversations);

		Assert::count(3, $conversations);
		Assert::contains($conversation1->getId(), $ids);
		Assert::contains($conversation2->getId(), $ids);
		Assert::contains($conversation3->getId(), $ids);
	}


	public function testCountUnreadByUser()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$user = $this->appUsers->create();

		$conversation = $this->conversations->createConversation($creator);
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));

		$conversation = $this->conversations->createConversation($creator);
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));

		$conversation = $this->conversations->createConversation($creator);
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));

		$count = $this->conversations->countUnreadByUser($user);

		Assert::same(2, $count);

		$count = $this->conversations->countUnreadByUser($creator);

		Assert::same(3, $count);
	}

}


run(new ConversationsFacadeTest);
