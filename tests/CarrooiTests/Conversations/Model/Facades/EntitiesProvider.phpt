<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\EntitiesProvider
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\EntitiesProviderTest
 * @author David Kudera
 */

namespace CarrooiTests\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Facades\EntitiesProvider;
use CarrooiTests\Conversations\TestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class EntitiesProviderTest extends TestCase
{


	public function testGetConversationClass()
	{
		$provider = new EntitiesProvider('\Nette\Object', '\DateTime');

		Assert::same('Nette\Object', $provider->getConversationClass());
	}


	public function testGetConversationItemClass()
	{
		$provider = new EntitiesProvider('\Nette\Object', '\DateTime');

		Assert::same('DateTime', $provider->getConversationItemClass());
	}


	public function testCreateConversationEntity()
	{
		$provider = new EntitiesProvider('stdClass', 'DateTime');

		Assert::type('stdClass', $provider->createConversationEntity());
	}


	public function testCreateConversationItemEntity()
	{
		$provider = new EntitiesProvider('stdClass', 'DateTime');

		Assert::type('DateTime', $provider->createConversationItemEntity());
	}

}


run(new EntitiesProviderTest);
