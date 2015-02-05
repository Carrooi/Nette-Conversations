<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationUserThreadsFacadeTest
 * @author David Kudera
 */

namespace CarrooiTests\Conversations\Model\Facades;

use CarrooiTests\Conversations\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ConversationUserThreadsFacadeTest extends TestCase
{


	/** @var \Carrooi\Conversations\Model\Facades\ConversationsFacade */
	private $conversations;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade */
	private $userThreads;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $appUsers;


	public function setUp()
	{
		$container = $this->createContainer();

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
		$this->messages = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');
		$this->userThreads = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade');
		$this->appUsers = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Users');
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->messages = null;
	}


	public function testIsUserInConversation_false()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::false($this->userThreads->isUserInConversation($conversation, $this->appUsers->create()));
	}


	public function testIsUserInConversation_true()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::true($this->userThreads->isUserInConversation($conversation, $creator));

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		Assert::true($this->userThreads->isUserInConversation($conversation, $user));
	}


	public function testAddUserToConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();
		$userThread = $this->userThreads->addUserToConversation($conversation, $user);

		Assert::notSame(null, $userThread->getId());
		Assert::same($user->getId(), $userThread->getUser()->getId());
		Assert::same($conversation->getId(), $userThread->getConversation()->getId());
	}


	public function testAddUserToConversation_alreadyExists()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->userThreads->addUserToConversation($conversation, $creator);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $creator->getId(). ' is already in conversation '. $conversation->getId(). '.');
	}


	public function testAddUserToConversation_denied()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->userThreads->unlinkUserFromConversation($conversation, $user);

		Assert::false($this->userThreads->isUserInConversation($conversation, $user));

		$this->userThreads->addUserToConversation($conversation, $user);

		Assert::true($this->userThreads->isUserInConversation($conversation, $user));
	}


	public function testAddUserToConversation_cloneOldItems()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->conversations->sendItem($conversation, $creator, $this->messages->create('lorem'));

		foreach ($this->conversations->findAllItemsByConversationAndUser($conversation, $creator) as $item) {
			$this->conversations->removeItem($item);
		}

		Assert::count(0, $this->conversations->findAllItemsByConversationAndUser($conversation, $creator));

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		Assert::count(3, $this->conversations->findAllItemsByConversationAndUser($conversation, $user));
	}


	public function testUnlinkUserFromConversation_creator()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->userThreads->unlinkUserFromConversation($conversation, $creator);
		}, 'Carrooi\Conversations\InvalidStateException', 'Can not unlink creator from conversation '. $conversation->getId(). '.');
	}


	public function testUnlinkUserFromConversation_notInConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();

		Assert::exception(function() use ($conversation, $user) {
			$this->userThreads->unlinkUserFromConversation($conversation, $user);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $user->getId(). ' is not in conversation '. $conversation->getId().'.');
	}


	public function testUnlinkUserFromConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->userThreads->unlinkUserFromConversation($conversation, $user);

		Assert::false($this->userThreads->isUserInConversation($conversation, $user));

		$userThread = $this->userThreads->findUserThreadByConversationAndUser($conversation, $user, false);

		Assert::notSame(null, $userThread);
		Assert::false($userThread->isAllowed());
	}


	public function testRemoveUserFromConversation_creator()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::exception(function() use ($conversation, $creator) {
			$this->userThreads->removeUserFromConversation($conversation, $creator);
		}, 'Carrooi\Conversations\InvalidStateException', 'Can not remove creator from conversation '. $conversation->getId(). '.');
	}


	public function testRemoveUserFromConversation_notInConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();

		Assert::exception(function() use ($conversation, $user) {
			$this->userThreads->removeUserFromConversation($conversation, $user);
		}, 'Carrooi\Conversations\InvalidStateException', 'User '. $user->getId(). ' is not in conversation '. $conversation->getId().'.');
	}


	public function testRemoveUserFromConversation()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		$this->userThreads->removeUserFromConversation($conversation, $user);

		Assert::false($this->userThreads->isUserInConversation($conversation, $user));
	}


	public function testFindUserThreadByConversationAndUser_notExists()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::null($this->userThreads->findUserThreadByConversationAndUser($conversation, $this->appUsers->create()));
	}


	public function testFindUserThreadByConversationAndUser()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		Assert::notSame(null, $this->userThreads->findUserThreadByConversationAndUser($conversation, $creator));

		$user = $this->appUsers->create();
		$this->userThreads->addUserToConversation($conversation, $user);

		Assert::notSame(null, $this->userThreads->findUserThreadByConversationAndUser($conversation, $user));
	}


	public function testFindOriginalUserThread()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$originalThread = $this->userThreads->findOriginalUserThread($conversation);

		Assert::notSame(null, $originalThread);
		Assert::null($originalThread->getUser());
	}

}


run(new ConversationUserThreadsFacadeTest);
