<?php

/**
 * Test: Carrooi\Conversations\Model\Facades\AssociationsManager
 *
 * @testCase CarrooiTests\Conversations\Model\Facades\AssociationsManagerTest
 * @author David Kudera
 */

namespace CarrooiTests\Conversations\Model\Facades;

use Carrooi\Conversations\Model\Facades\AssociationsManager;
use CarrooiTests\Conversations\TestCase;
use Nette\Object;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class AssociationsManagerTest extends TestCase
{


	public function testFunctionality()
	{
		$manager = new AssociationsManager;

		Assert::count(0, $manager->getAssociations());

		$manager->addAssociation('\Nette\Object', 'object');

		Assert::same('object', $manager->getAssociation('Nette\Object'));
		Assert::same('object', $manager->getAssociation('\Nette\Object'));

		Assert::same([
			'Nette\Object' => 'object',
		], $manager->getAssociations());

		Assert::true($manager->hasAssociation('Nette\Object'));
		Assert::true($manager->hasAssociation('\Nette\Object'));
	}


	public function testFunctionality_extendedClass()
	{
		$manager = new AssociationsManager;

		Assert::count(0, $manager->getAssociations());

		$manager->addAssociation('\Nette\Object', 'object');

		Assert::same('object', $manager->getAssociation('CarrooiTests\Conversations\Model\Facades\SuperObject'));
		Assert::same('object', $manager->getAssociation('\CarrooiTests\Conversations\Model\Facades\SuperObject'));

		Assert::same([
			'Nette\Object' => 'object',
			'CarrooiTests\Conversations\Model\Facades\SuperObject' => 'object',
		], $manager->getAssociations());

		Assert::true($manager->hasAssociation('CarrooiTests\Conversations\Model\Facades\SuperObject'));
		Assert::true($manager->hasAssociation('\CarrooiTests\Conversations\Model\Facades\SuperObject'));
	}

}


/**
 *
 * @author David Kudera
 */
class SuperObject extends Object {}


run(new AssociationsManagerTest);
