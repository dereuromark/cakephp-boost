<?php
declare(strict_types=1);

use Cake\Log\Log;

/**
 * Boost Plugin Bootstrap
 */

if (!Log::getConfig('mcp')) {
	Log::setConfig('mcp', [
		'className' => 'File',
		'path' => LOGS,
		'file' => 'mcp-debug',
		'scopes' => ['mcp'],
	]);
}
