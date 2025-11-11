<?php
declare(strict_types=1);

namespace CakeBoost\Documentation\Parser;

/**
 * CakePHP Book Parser
 *
 * Parses CakePHP Book documentation for indexing.
 * For MVP, this provides sample documentation entries.
 * In production, this would fetch from the official CakePHP docs repo.
 */
class BookParser {

	/**
	 * Parse CakePHP Book documentation
	 *
	 * @return array<array<string, mixed>> Parsed documents
	 */
	public function parse(): array {
		// For MVP, return sample documentation entries
		// In production, this would fetch from https://github.com/cakephp/docs
		return $this->getSampleDocumentation();
	}

	/**
	 * Get sample documentation entries for testing
	 *
	 * @return array<array<string, mixed>> Sample documents
	 */
	protected function getSampleDocumentation(): array {
		return [
			[
				'title' => 'Database Basics - Saving Data',
				'content' => 'To save data, first create a new entity using newEntity(), then call save() on the table object. ' .
					'Example: $article = $this->Articles->newEntity($data); $this->Articles->save($article);',
				'url' => 'https://book.cakephp.org/5/en/orm/saving-data.html',
				'category' => 'orm',
			],
			[
				'title' => 'Associations - BelongsToMany',
				'content' => 'The belongsToMany association is used when two models are associated through a join table. ' .
					'Example: $this->belongsToMany(\'Tags\', [\'joinTable\' => \'articles_tags\']);',
				'url' => 'https://book.cakephp.org/5/en/orm/associations.html#belongstomany',
				'category' => 'orm',
			],
			[
				'title' => 'Validation Rules',
				'content' => 'Add validation rules in your Table class validationDefault() method. ' .
					'Example: $validator->notEmptyString(\'title\')->minLength(\'title\', 10);',
				'url' => 'https://book.cakephp.org/5/en/core-libraries/validation.html',
				'category' => 'validation',
			],
			[
				'title' => 'Controllers - Request and Response',
				'content' => 'Controllers receive a request object via $this->request and return responses. ' .
					'Example: $data = $this->request->getData(); return $this->response->withType(\'json\')->withStringBody(json_encode($data));',
				'url' => 'https://book.cakephp.org/5/en/controllers/request-response.html',
				'category' => 'controller',
			],
			[
				'title' => 'Query Builder - Finding Data',
				'content' => 'Use the query builder to find data with conditions. ' .
					'Example: $query = $this->Articles->find()->where([\'published\' => true])->orderBy([\'created\' => \'DESC\']);',
				'url' => 'https://book.cakephp.org/5/en/orm/query-builder.html',
				'category' => 'orm',
			],
			[
				'title' => 'Authentication Plugin',
				'content' => 'CakePHP 5 uses the Authentication plugin for handling user authentication. ' .
					'Configure authenticators and identifiers in your Application class getAuthenticationService() method.',
				'url' => 'https://book.cakephp.org/5/en/controllers/middleware.html#authentication',
				'category' => 'authentication',
			],
			[
				'title' => 'Authorization Plugin',
				'content' => 'The Authorization plugin provides role-based access control. ' .
					'Implement policies and use the AuthorizationComponent to check permissions.',
				'url' => 'https://book.cakephp.org/5/en/controllers/middleware.html#authorization',
				'category' => 'authorization',
			],
			[
				'title' => 'Middleware - Creating Custom Middleware',
				'content' => 'Create middleware by implementing MiddlewareInterface with process() method. ' .
					'Add to middleware queue in Application.php middleware() method.',
				'url' => 'https://book.cakephp.org/5/en/controllers/middleware.html',
				'category' => 'middleware',
			],
			[
				'title' => 'Testing - Controller Tests',
				'content' => 'Controller tests extend IntegrationTestCase. Use $this->get() and $this->post() to test actions. ' .
					'Example: $this->get([\'controller\' => \'Articles\', \'action\' => \'view\', 1]);',
				'url' => 'https://book.cakephp.org/5/en/development/testing.html',
				'category' => 'testing',
			],
			[
				'title' => 'Email - Sending Emails',
				'content' => 'Use Mailer class to send emails. Example: $mailer = new Mailer(); $mailer->setTo(\'user@example.com\')->setSubject(\'Hello\')->deliver(\'Message body\');',
				'url' => 'https://book.cakephp.org/5/en/core-libraries/email.html',
				'category' => 'email',
			],
			[
				'title' => 'Routing - Route Prefixes',
				'content' => 'Use route prefixes to group related routes. Example: $routes->prefix(\'Admin\', function($routes) { $routes->connect(\'/users\', [\'controller\' => \'Users\', \'action\' => \'index\']); });',
				'url' => 'https://book.cakephp.org/5/en/development/routing.html',
				'category' => 'routing',
			],
			[
				'title' => 'Events System',
				'content' => 'CakePHP uses an event system for extensibility. Listen to events with EventManager and trigger with dispatchEvent(). ' .
					'Example: $this->getEventManager()->on(\'Model.beforeSave\', function($event, $entity) {});',
				'url' => 'https://book.cakephp.org/5/en/core-libraries/events.html',
				'category' => 'events',
			],
			[
				'title' => 'Behaviors - Adding Behaviors',
				'content' => 'Add behaviors to tables in initialize() method. Example: $this->addBehavior(\'Timestamp\'); or $this->addBehavior(\'Sluggable\', [\'unique\' => true]);',
				'url' => 'https://book.cakephp.org/5/en/orm/behaviors.html',
				'category' => 'orm',
			],
			[
				'title' => 'Components - Creating Custom Components',
				'content' => 'Create components that extend Component class. Load in controllers with $this->loadComponent(). ' .
					'Example: $this->loadComponent(\'Flash\'); or $this->loadComponent(\'Auth\', [\'loginAction\' => [...]]);',
				'url' => 'https://book.cakephp.org/5/en/controllers/components.html',
				'category' => 'controller',
			],
			[
				'title' => 'Helpers - View Helpers',
				'content' => 'Helpers assist with view rendering. Load in templates with $this->loadHelper() or configure in AppView. ' .
				'Common helpers: Form, Html, Flash, Paginator, Time.',
				'url' => 'https://book.cakephp.org/5/en/views/helpers.html',
				'category' => 'view',
			],
			[
				'title' => 'Pagination',
				'content' => 'Use paginate() in controllers to paginate query results. Example: $articles = $this->paginate($this->Articles); ' .
					'Then use PaginatorHelper in views to render pagination controls.',
				'url' => 'https://book.cakephp.org/5/en/controllers/pagination.html',
				'category' => 'controller',
			],
			[
				'title' => 'Database Transactions',
				'content' => 'Wrap database operations in transactions for atomicity. ' .
					'Example: $connection->transactional(function($connection) { // operations }); or use $connection->begin(), commit(), rollback().',
				'url' => 'https://book.cakephp.org/5/en/orm/database-basics.html#transactions',
				'category' => 'orm',
			],
			[
				'title' => 'Caching',
				'content' => 'CakePHP provides caching via Cache facade. Example: Cache::write(\'key\', $data, \'config\'); $data = Cache::read(\'key\', \'config\');',
				'url' => 'https://book.cakephp.org/5/en/core-libraries/caching.html',
				'category' => 'caching',
			],
			[
				'title' => 'Form Validation - Custom Validators',
				'content' => 'Create custom validation rules as methods in your Table class. ' .
					'Example: public function validationDefault(Validator $validator): Validator { $validator->add(\'field\', \'custom\', [\'rule\' => [$this, \'customRule\']]); }',
				'url' => 'https://book.cakephp.org/5/en/core-libraries/validation.html#custom-validation-rules',
				'category' => 'validation',
			],
			[
				'title' => 'Shells and Commands - Console Commands',
				'content' => 'Create console commands by extending Command class. Override execute() method and use ConsoleIo for input/output. ' .
					'Run with bin/cake command_name.',
				'url' => 'https://book.cakephp.org/5/en/console-commands/commands.html',
				'category' => 'console',
			],
		];
	}

}
