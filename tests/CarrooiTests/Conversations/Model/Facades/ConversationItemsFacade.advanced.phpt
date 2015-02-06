<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationItemsFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationItemsFacadeAdvancedTest
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
class ConversationItemsFacadeAdvancedTest extends TestCase
{


	/** @var \Carrooi\Conversations\Model\Facades\ConversationsFacade */
	private $conversations;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationItemsFacade */
	private $items;

	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Users */
	private $appUsers;

	/** @var \CarrooiTests\ConversationsApp\Model\Facades\Books */
	private $books;


	public function setUp()
	{
		$this->database = 'advanced';
		$container = $this->createContainer('config.advanced');

		$this->conversations = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationsFacade');
		$this->items = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationItemsFacade');
		$this->messages = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');
		$this->appUsers = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Users');
		$this->books = $container->getByType('CarrooiTests\ConversationsApp\Model\Facades\Books');
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->messages = null;
	}


	public function testSendItem()
	{
		$this->createContainer();

		$creator = $this->appUsers->create();
		$conversation = $this->conversations->createConversation($creator);

		$this->items->sendItem($conversation, $creator, $this->books->create());
		$this->items->sendItem($conversation, $creator, $this->messages->create('lorem'));
		$this->items->sendItem($conversation, $creator, $this->books->create());

		$items = $this->items->findAllItemsByConversationAndUser($conversation, $creator)->toArray();
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


run(new ConversationItemsFacadeAdvancedTest);
