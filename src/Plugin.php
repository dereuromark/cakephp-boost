<?php
declare(strict_types=1);

namespace CakeBoost;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;

/**
 * Plugin for Boost
 */
class Plugin extends BasePlugin {

	/**
	 * @param \Cake\Core\PluginApplicationInterface $app The application loading the plugin.
	 * @return void
	 */
	public function bootstrap(PluginApplicationInterface $app): void {
	}

	/**
	 * Add console commands for the plugin.
	 *
	 * @param \Cake\Console\CommandCollection $commands The command collection to update
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		$commands->add('boost_search', Command\BoostSearchCommand::class);
		$commands->add('boost_index', Command\BoostIndexCommand::class);
		$commands->add('boost_schema', Command\BoostSchemaCommand::class);
		$commands->add('boost_mcp_server', Command\BoostMcpServerCommand::class);

		return $commands;
	}

}
