<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\UsersFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\UsersFacadeTest
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
class UsersFacadeTest extends TestCase
{


	/** @var \Carrooi\Conversations\Model\Facades\ConversationsFacade */
	private $conversations;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;

	/** @var \Carrooi\Conversations\Model\Facades\UsersFacade */
	private $users;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationUserThreadsFacade */
	private $userThreads;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $appUsers;


	public function setUp()
	{
		$container = $this->createContainer();

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
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


	public function testFindAllUsersInConversation()
	{
		$this->createContainer();

		$conversation = $this->conversations->createConversation($this->appUsers->create());

		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());

		$this->userThreads->addUserToConversation($this->conversations->createConversation($this->appUsers->create()), $this->appUsers->create());
		$this->userThreads->addUserToConversation($this->conversations->createConversation($this->appUsers->create()), $this->appUsers->create());
		$this->userThreads->addUserToConversation($this->conversations->createConversation($this->appUsers->create()), $this->appUsers->create());

		Assert::count(5, $this->users->findAllUsersInConversation($conversation));
	}


	public function testCountUsersInConversation()
	{
		$this->createContainer();

		$conversation = $this->conversations->createConversation($this->appUsers->create());

		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());
		$this->userThreads->addUserToConversation($conversation, $this->appUsers->create());

		$this->userThreads->addUserToConversation($this->conversations->createConversation($this->appUsers->create()), $this->appUsers->create());
		$this->userThreads->addUserToConversation($this->conversations->createConversation($this->appUsers->create()), $this->appUsers->create());
		$this->userThreads->addUserToConversation($this->conversations->createConversation($this->appUsers->create()), $this->appUsers->create());

		Assert::same(5, $this->users->countUsersInConversation($conversation));
	}

}


run(new UsersFacadeTest);
