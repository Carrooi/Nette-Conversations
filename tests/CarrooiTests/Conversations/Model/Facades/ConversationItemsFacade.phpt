<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationItemsFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationItemsFacadeTest
 * @author David Kudera
 */

namespace CarrooiTests\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Entities\IConversationAttachment;
use Carrooi\Conversations\Model\Entities\TConversationAttachment;
use CarrooiTests\Conversations\TestCase;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette\Object;
use Tester\Assert;
use Tester\Environment;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ConversationItemsFacadeTest extends TestCase
{


	/** @var \Carrooi\Conversations\Model\Facades\ConversationsFacade */
	private $conversations;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationItemsFacade */
	private $items;

	/** @var \Carrooi\Conversations\Model\Facades\UsersFacade */
	private $users;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade */
	private $userThreads;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $appUsers;


	public function setUp()
	{
		$container = $this->createContainer();

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
		$this->items = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationItemsFacade');
		$this->messages = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');
		$this->users = $container->getByType('Carrooi\Conversations\Model\Facades\UsersFacade');
		$this->userThreads = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade');
		$this->appUsers = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Users');
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->messages = null;
	}


	public function testAddItemToConversation_notInConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);
		$message = $this->messages->create('lorem');

		$user = $this->appUsers->create();

		Assert::exception(function() use ($conversation, $user, $message) {
			$this->items->addItemToConversation($conversation, $user, $message, $user);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $user->getId(). ' is not in conversation '. $conversation->getId(). '.');
	}


	public function testAddItemToConversation_unknownAssociation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->items->addItemToConversation($conversation, $creator, new FakeAttachment, $creator);
		}, 'Carrooi\Conversations\InvalidArgumentException', 'Class CarrooiTests\Conversations\Model\Facades\FakeAttachment is not registered as custom conversation attachment entity.');
	}


	public function testAddItemToConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$userThread = $this->userThreads->findUserThreadByConversationAndUser($conversation, $creator);

		$message = $this->messages->create('lorem');

		$item = $this->items->addItemToConversation($conversation, $creator, $message, $creator);

		Assert::notSame(null, $item->getId());
		Assert::notSame(null, $item->getCreatedAt());
		Assert::same($creator->getId(), $item->getSender()->getId());
		Assert::same($userThread->getId(), $item->getConversationUserThread()->getId());
		Assert::true($item->hasMessage());
		Assert::same('lorem', $item->getMessage()->getText());
		Assert::count(1, $item->getMessage()->getConversationItems());
		Assert::same($item->getMessage()->getId(), $item->getMessage()->getConversationItems()[0]->getMessage()->getId());

		Assert::true($item->isRead());

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);
		$item = $this->items->addItemToConversation($conversation, $creator, $message, $user);

		Assert::false($item->isRead());
	}


	public function testSendItem()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());

		$message = $this->messages->create('lorem');

		$this->items->sendItem($conversation, $creator, $message);

		$users = $this->users->findAllUsersInConversation($conversation);
		Assert::count(5, $users);

		foreach ($users as $user) {
			$items = $this->items->findAllItemsByConversationAndUser($conversation, $user);
			$item = $items->toArray()[0];

			/** @var \Carrooi\Conversations\Model\Entities\IConversationItem $item */

			Assert::count(1, $items);
			Assert::true($item->hasMessage());
			Assert::same('lorem', $item->getMessage()->getText());

			if ($user->getId() === $creator->getId()) {
				Assert::true($item->isRead());
			} else {
				Assert::false($item->isRead());
			}
		}
	}


	public function testFindItemById()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$item = $this->items->findAllItemsByConversationAndUser($conversation, $creator)->toArray()[0];
		/** @var \Carrooi\Conversations\Model\Entities\IConversationItem $item */

		Assert::notSame(null, $this->items->findItemById($item->getId()));
		Assert::null($this->items->findItemById(555));
	}


	public function testIsItemInConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation1 = $this->conversations->createConversation($creator);

		$item1 = $this->items->addItemToConversation($conversation1, $creator, $this->messages->create('lorem'), $creator);

		$conversation2 = $this->conversations->createConversation($creator);

		$item2 = $this->items->addItemToConversation($conversation2, $creator, $this->messages->create('lorem'), $creator);

		Assert::true($this->items->isItemInConversation($conversation1, $creator, $item1));
		Assert::false($this->items->isItemInConversation($conversation1, $creator, $item2));

		Assert::false($this->items->isItemInConversation($conversation2, $creator, $item1));
		Assert::true($this->items->isItemInConversation($conversation2, $creator, $item2));
	}


	public function testRemoveItem()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$item1 = $this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$item2 = $this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);

		$this->items->removeItem($item1);

		Assert::null($this->items->findItemById($item1->getId()));
		Assert::notSame(null, $this->items->findItemById($item2->getId()));
	}


	public function testFindAllItemsByConversationAndUser()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);
		$this->items->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $user);

		$items = $this->items->findAllItemsByConversationAndUser($conversation, $creator);

		Assert::count(4, $items);
	}


	public function testFindAllUnreadItems()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));

		$this->items->setReadConversation($conversation, $creator);

		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));

		Assert::count(2, $this->items->findAllUnreadItems($conversation, $creator));
	}


	public function testFindAllOriginalItems()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$items = $this->items->findAllItemsByConversationAndUser($conversation, $creator)->toArray();
		/** @var \Carrooi\Conversations\Model\Entities\IConversationItem[] $items */

		Assert::count(1, $items);

		$this->items->removeItem($items[0]);

		Assert::count(0, $this->items->findAllItemsByConversationAndUser($conversation, $creator));

		$items = $this->items->findAllOriginalItems($conversation)->toArray();

		Assert::count(1, $items);
		Assert::true($items[0]->hasMessage());
		Assert::same('lorem', $items[0]->getMessage()->getText());
	}


	public function testSetReadConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $user, $this->messages->create('lorem'));

		$items = $this->items->findAllUnreadItems($conversation, $creator)->toArray();
		/** @var \Carrooi\Conversations\Model\Entities\IConversationItem[] $items */

		Assert::count(3, $items);
		Assert::false($items[0]->isRead());
		Assert::false($items[1]->isRead());
		Assert::false($items[2]->isRead());

		$this->items->setReadConversation($conversation, $creator);

		$items = $this->items->findAllUnreadItems($conversation, $creator)->toArray();

		Assert::count(0, $items);
	}


	public function testCloneOriginalItems()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));

		foreach ($this->items->findAllItemsByConversationAndUser($conversation, $creator) as $item) {
			$this->items->removeItem($item);
		}

		Assert::count(0, $this->items->findAllItemsByConversationAndUser($conversation, $creator));

		$user = $this->appUsers->create();

		$userThread = $this->userThreads->addUserToConversation($conversation, $user);
		$this->items->cloneOriginalItems($conversation, $userThread);

		Assert::count(3, $this->items->findAllItemsByConversationAndUser($conversation, $user));
	}

}


/**
 *
 * @author David KUdera
 */
class FakeAttachment extends Object implements IConversationAttachment
{

	use Identifier;

	use TConversationAttachment;

}


run(new ConversationItemsFacadeTest);
