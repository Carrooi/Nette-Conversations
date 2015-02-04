<?php

namespace CarrooiTests\Conversations;

use Nette\Configurator;
use Tester\FileMock;
use Tester\TestCase as BaseTestCase;

/**
 *
 * @author David Kudera
 */
class TestCase extends BaseTestCase
{


	/** @var string */
	protected $database = null;


	/**
	 * @param string $customConfig
	 * @return \Nette\DI\Container
	 */
	protected function createContainer($customConfig = null)
	{
		$database = $this->database === null ? 'database' : 'database_'. $this->database;
		copy(__DIR__. '/../ConversationsApp/Model/'. $database, TEMP_DIR. '/database');

		$config = new Configurator;
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($customConfig)]]);
		$config->addParameters(['appDir' => __DIR__. '/../ConversationsApp']);
		$config->addConfig(__DIR__. '/../ConversationsApp/config/config.neon');
		$config->addConfig(FileMock::create('parameters: {databasePath: %tempDir%/database}', 'neon'));

		if ($customConfig) {
			if (pathinfo($customConfig, PATHINFO_EXTENSION) !== 'neon') {
				$customConfig = __DIR__. '/../ConversationsApp/config/'. $customConfig. '.neon';
			}

			$config->addConfig($customConfig);
		}

		return $config->createContainer();
	}


	public function tearDown()
	{
		$this->database = null;
	}

}
