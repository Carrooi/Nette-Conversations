<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationsFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationsFacadeTest
 * @author David Kudera
 */

namespace CarrooiTests\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Entities\IConversation;
use Carrooi\Conversations\Model\Entities\IConversationAttachment;
use Carrooi\Conversations\Model\Entities\TConversationAttachment;
use CarrooiTests\Conversations\TestCase;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Nette\Object;
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

	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $users;


	/**
	 * @param string $customConfig
	 * @return \Nette\DI\Container
	 */
	protected function createContainer($customConfig = null)
	{
		$container = parent::createContainer($customConfig);

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
		$this->messages = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');
		$this->users = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Users');

		return $container;
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->conversations = $this->users = null;
	}


	public function testCreateConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::notSame(null, $conversation->getId());
		Assert::notSame(null, $conversation->getCreatedAt());
		Assert::same($creator->getId(), $conversation->getCreator()->getId());
	}


	public function testAddUserToConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();
		$userThread = $this->conversations->addUserToConversation($conversation, $user);

		Assert::notSame(null, $userThread->getId());
		Assert::same($user->getId(), $userThread->getUser()->getId());
		Assert::same($conversation->getId(), $userThread->getConversation()->getId());
	}


	public function testAddUserToConversation_alreadyExists()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->conversations->addUserToConversation($conversation, $creator);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $creator->getId(). ' is already in conversation '. $conversation->getId(). '.');
	}


	public function testAddUserToConversation_denied()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->unlinkUserFromConversation($conversation, $user);

		Assert::false($this->conversations->isUserInConversation($conversation, $user));

		$this->conversations->addUserToConversation($conversation, $user);

		Assert::true($this->conversations->isUserInConversation($conversation, $user));
	}


	public function testAddUserToConversation_cloneOldItems()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));

		foreach ($this->conversations->findAllItemsByConversationAndUser($conversation, $creator) as $item) {
			$this->conversations->removeItem($item);
		}

		Assert::count(0, $this->conversations->findAllItemsByConversationAndUser($conversation, $creator));

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		Assert::count(3, $this->conversations->findAllItemsByConversationAndUser($conversation, $user));
	}


	public function testFindUserThreadByConversationAndUser_notExists()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::null($this->conversations->findUserThreadByConversationAndUser($conversation, $this->users->create()));
	}


	public function testFindUserThreadByConversationAndUser()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::notSame(null, $this->conversations->findUserThreadByConversationAndUser($conversation, $creator));

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		Assert::notSame(null, $this->conversations->findUserThreadByConversationAndUser($conversation, $user));
	}


	public function testFindOriginalUserThread()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$originalThread = $this->conversations->findOriginalUserThread($conversation);

		Assert::notSame(null, $originalThread);
		Assert::null($originalThread->getUser());
	}


	public function testIsUserInConversation_false()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::false($this->conversations->isUserInConversation($conversation, $this->users->create()));
	}


	public function testIsUserInConversation_true()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::true($this->conversations->isUserInConversation($conversation, $creator));

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		Assert::true($this->conversations->isUserInConversation($conversation, $user));
	}


	public function testRemoveUserFromConversation_creator()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->conversations->removeUserFromConversation($conversation, $creator);
		}, 'Carrooi\Conversations\InvalidStateException', 'Can not remove creator from conversation '. $conversation->getId(). '.');
	}


	public function testRemoveUserFromConversation_notInConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();

		Assert::exception(function() use ($conversation, $user) {
			$this->conversations->removeUserFromConversation($conversation, $user);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $user->getId(). ' is not in conversation '. $conversation->getId().'.');
	}


	public function testRemoveUserFromConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->removeUserFromConversation($conversation, $user);

		Assert::false($this->conversations->isUserInConversation($conversation, $user));
	}


	public function testUnlinkUserFromConversation_creator()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->conversations->unlinkUserFromConversation($conversation, $creator);
		}, 'Carrooi\Conversations\InvalidStateException', 'Can not unlink creator from conversation '. $conversation->getId(). '.');
	}


	public function testUnlinkUserFromConversation_notInConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();

		Assert::exception(function() use ($conversation, $user) {
			$this->conversations->unlinkUserFromConversation($conversation, $user);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $user->getId(). ' is not in conversation '. $conversation->getId().'.');
	}


	public function testUnlinkUserFromConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->unlinkUserFromConversation($conversation, $user);

		Assert::false($this->conversations->isUserInConversation($conversation, $user));

		$userThread = $this->conversations->findUserThreadByConversationAndUser($conversation, $user, false);

		Assert::notSame(null, $userThread);
		Assert::false($userThread->isAllowed());
	}


	public function testFindAllByUser()
	{
		$this->createContainer();

		$creator = $this->users->create();

		$this->conversations->createConversation($creator);
		$this->conversations->createConversation($creator);
		$this->conversations->createConversation($creator);

		$user = $this->users->create();

		$conversation = $this->conversations->createConversation($this->users->create());
		$this->conversations->addUserToConversation($conversation, $user);

		$conversation = $this->conversations->createConversation($this->users->create());
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->unlinkUserFromConversation($conversation, $user);

		Assert::count(3, $this->conversations->findAllByUser($creator));
		Assert::count(1, $this->conversations->findAllByUser($user));
	}


	public function testFindAllUsersInConversation()
	{
		$this->createContainer();

		$conversation = $this->conversations->createConversation($this->users->create());

		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());

		$this->conversations->addUserToConversation($this->conversations->createConversation($this->users->create()), $this->users->create());
		$this->conversations->addUserToConversation($this->conversations->createConversation($this->users->create()), $this->users->create());
		$this->conversations->addUserToConversation($this->conversations->createConversation($this->users->create()), $this->users->create());

		Assert::count(5, $this->conversations->findAllUsersInConversation($conversation));
	}


	public function testCountUsersInConversation()
	{
		$this->createContainer();

		$conversation = $this->conversations->createConversation($this->users->create());

		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());

		$this->conversations->addUserToConversation($this->conversations->createConversation($this->users->create()), $this->users->create());
		$this->conversations->addUserToConversation($this->conversations->createConversation($this->users->create()), $this->users->create());
		$this->conversations->addUserToConversation($this->conversations->createConversation($this->users->create()), $this->users->create());

		Assert::same(5, $this->conversations->countUsersInConversation($conversation));
	}


	public function testCountByUser()
	{
		$this->createContainer();

		$user = $this->users->create();

		$this->conversations->createConversation($user);
		$this->conversations->createConversation($user);
		$this->conversations->createConversation($user);
		$this->conversations->createConversation($user);

		$conversation = $this->conversations->createConversation($this->users->create());
		$this->conversations->addUserToConversation($conversation, $user);
		$this->conversations->unlinkUserFromConversation($conversation, $user);

		Assert::same(4, $this->conversations->countByUser($user));
	}


	public function testAddItemToConversation_notInConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);
		$message = $this->messages->create('lorem');

		$user = $this->users->create();

		Assert::exception(function() use ($conversation, $user, $message) {
			$this->conversations->addItemToConversation($conversation, $user, $message, $user);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $user->getId(). ' is not in conversation '. $conversation->getId(). '.');
	}


	public function testAddItemToConversation_unknownAssociation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->conversations->addItemToConversation($conversation, $creator, new FakeAttachment, $creator);
		}, 'Carrooi\Conversations\InvalidArgumentException', 'Class CarrooiTests\Conversations\Model\Facades\FakeAttachment is not registered as custom conversation attachment entity.');
	}


	public function testAddItemToConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$userThread = $this->conversations->findUserThreadByConversationAndUser($conversation, $creator);

		$message = $this->messages->create('lorem');

		$item = $this->conversations->addItemToConversation($conversation, $creator, $message, $creator);

		Assert::notSame(null, $item->getId());
		Assert::notSame(null, $item->getCreatedAt());
		Assert::same($creator->getId(), $item->getSender()->getId());
		Assert::same($userThread->getId(), $item->getConversationUserThread()->getId());
		Assert::true($item->hasMessage());
		Assert::same('lorem', $item->getMessage()->getText());
		Assert::count(1, $item->getMessage()->getConversationItems());
		Assert::same($item->getMessage()->getId(), $item->getMessage()->getConversationItems()[0]->getMessage()->getId());

		Assert::true($item->isRead());

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);
		$item = $this->conversations->addItemToConversation($conversation, $creator, $message, $user);

		Assert::false($item->isRead());
	}


	public function testFindAllItemsByConversationAndUser()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);
		$this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $user);

		$items = $this->conversations->findAllItemsByConversationAndUser($conversation, $creator);

		Assert::count(4, $items);
	}


	public function testSendItem()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());
		$this->conversations->addUserToConversation($conversation, $this->users->create());

		$message = $this->messages->create('lorem');

		$this->conversations->sendItem($conversation, $creator, $message);

		$users = $this->conversations->findAllUsersInConversation($conversation);
		Assert::count(5, $users);

		foreach ($users as $user) {
			$items = $this->conversations->findAllItemsByConversationAndUser($conversation, $user);
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


	public function testFindAllUnreadByUser()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$user = $this->users->create();

		$conversation1 = $this->conversations->createConversation($creator);
		$this->conversations->addUserToConversation($conversation1, $user);

		$this->conversations->sendItem($conversation1, $creator, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation1, $creator, $this->messages->create('lorem'));

		$this->conversations->sendItem($conversation1, $user, $this->messages->create('lorem'));

		$conversation2 = $this->conversations->createConversation($creator);
		$this->conversations->addUserToConversation($conversation2, $user);

		$this->conversations->sendItem($conversation2, $creator, $this->messages->create('lorem'));

		$this->conversations->sendItem($conversation2, $user, $this->messages->create('lorem'));

		$conversation3 = $this->conversations->createConversation($creator);
		$this->conversations->addUserToConversation($conversation3, $user);

		$this->conversations->sendItem($conversation3, $user, $this->messages->create('lorem'));

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

		$creator = $this->users->create();
		$user = $this->users->create();

		$conversation = $this->conversations->createConversation($creator);
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));

		$conversation = $this->conversations->createConversation($creator);
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));

		$conversation = $this->conversations->createConversation($creator);
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));

		$count = $this->conversations->countUnreadByUser($user);

		Assert::same(2, $count);

		$count = $this->conversations->countUnreadByUser($creator);

		Assert::same(3, $count);
	}


	public function testFindItemById()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$item = $this->conversations->findAllItemsByConversationAndUser($conversation, $creator)->toArray()[0];
		/** @var \Carrooi\Conversations\Model\Entities\IConversationItem $item */

		Assert::notSame(null, $this->conversations->findItemById($item->getId()));
		Assert::null($this->conversations->findItemById(555));
	}


	public function testIsItemInConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation1 = $this->conversations->createConversation($creator);

		$item1 = $this->conversations->addItemToConversation($conversation1, $creator, $this->messages->create('lorem'), $creator);

		$conversation2 = $this->conversations->createConversation($creator);

		$item2 = $this->conversations->addItemToConversation($conversation2, $creator, $this->messages->create('lorem'), $creator);

		Assert::true($this->conversations->isItemInConversation($conversation1, $creator, $item1));
		Assert::false($this->conversations->isItemInConversation($conversation1, $creator, $item2));

		Assert::false($this->conversations->isItemInConversation($conversation2, $creator, $item1));
		Assert::true($this->conversations->isItemInConversation($conversation2, $creator, $item2));
	}


	public function testRemoveItem()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$item1 = $this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);
		$item2 = $this->conversations->addItemToConversation($conversation, $creator, $this->messages->create('lorem'), $creator);

		$this->conversations->removeItem($item1);

		Assert::null($this->conversations->findItemById($item1->getId()));
		Assert::notSame(null, $this->conversations->findItemById($item2->getId()));
	}


	public function testFindAllOriginalItems()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));

		$items = $this->conversations->findAllItemsByConversationAndUser($conversation, $creator)->toArray();
		/** @var \Carrooi\Conversations\Model\Entities\IConversationItem[] $items */

		Assert::count(1, $items);

		$this->conversations->removeItem($items[0]);

		Assert::count(0, $this->conversations->findAllItemsByConversationAndUser($conversation, $creator));

		$items = $this->conversations->findAllOriginalItems($conversation)->toArray();

		Assert::count(1, $items);
		Assert::true($items[0]->hasMessage());
		Assert::same('lorem', $items[0]->getMessage()->getText());
	}


	public function setReadConversation()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));

		$items = $this->conversations->findAllItemsByConversationAndUser($conversation, $creator)->toArray();
		/** @var \Carrooi\Conversations\Model\Entities\IConversationItem[] $items */

		Assert::count(3, $items);
		Assert::false($items[0]->isRead());
		Assert::false($items[1]->isRead());
		Assert::false($items[2]->isRead());

		$this->conversations->setReadConversation($conversation, $user);

		$items = $this->conversations->findAllItemsByConversationAndUser($conversation, $creator)->toArray();

		Assert::count(3, $items);
		Assert::true($items[0]->isRead());
		Assert::true($items[1]->isRead());
		Assert::true($items[2]->isRead());
	}


	public function testFindAllUnreadItems()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->users->create();
		$this->conversations->addUserToConversation($conversation, $user);

		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));

		$this->conversations->setReadConversation($conversation, $creator);

		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $user, $this->messages->create('lorem'));

		Assert::count(2, $this->conversations->findAllUnreadItems($conversation, $creator));
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


run(new ConversationsFacadeTest);
