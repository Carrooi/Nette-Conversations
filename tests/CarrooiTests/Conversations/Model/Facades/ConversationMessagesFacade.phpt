<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\ConversationMessagesFacade
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\ConversationMessagesFacadeTest
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
class ConversationMessagesFacadeTest extends TestCase
{



	/** @var \Carrooi\Conversations\Model\Facades\ConversationMessagesFacade */
	private $messages;


	public function setUp()
	{
		$container = $this->createContainer();

		$this->messages = $container->getByType('Carrooi\Conversations\Model\Facades\ConversationMessagesFacade');
	}


	public function tearDown()
	{
		parent::tearDown();

		$this->messages = null;
	}


	public function testCreateMessage()
	{
		$this->createContainer();

		$message = $this->messages->create('lorem');

		Assert::notSame(null, $message->getId());
		Assert::same('lorem', $message->getText());
	}

}


run(new ConversationMessagesFacadeTest);
