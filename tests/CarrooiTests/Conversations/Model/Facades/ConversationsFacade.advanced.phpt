<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationsFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationsFacadeAdvancedTest
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
class ConversationsFacadeAdvancedTest extends TestCase
{



	/** @var \Carrooi\Conversations\Model\Facades\ConversationsFacade */
	private $conversations;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $users;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Books */
	private $books;


	/**
	 * @param string $customConfig
	 * @return \Nette\DI\Container
	 */
	protected function createContainer($customConfig = 'config.advanced')
	{
		$this->database = 'advanced';

		$container = parent::createContainer($customConfig);

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
		$this->users = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Users');
		$this->books = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Books');

		return $container;
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->conversations = $this->users = null;
	}


	public function testSendItem()
	{
		$this->createContainer();

		$creator = $this->users->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->conversations->sendItem($conversation, $creator, $this->books->create());
		$this->conversations->sendItem($conversation, $creator, $this->conversations->createMessage('lorem'));
		$this->conversations->sendItem($conversation, $creator, $this->books->create());

		$items = $this->conversations->findAllItemsByConversationAndUser($conversation, $creator)->toArray();
		/** @var \CarrooiTests\ConversationsApp\Model\Entities\ConversationItem[] $items */

		Assert::count(3, $items);

		Assert::true($items[0]->hasBook());
		Assert::false($items[0]->hasMessage());

		Assert::false($items[1]->hasBook());
		Assert::true($items[1]->hasMessage());

		Assert::true($items[2]->hasBook());
		Assert::false($items[2]->hasMessage());
	}

}


run(new ConversationsFacadeAdvancedTest);
