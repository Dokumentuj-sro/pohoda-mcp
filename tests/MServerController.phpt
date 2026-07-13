<?php declare(strict_types=1);

use DG\Pohoda\MServerController;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


test('stop() without closeApp issues only the listener stop', function () {
	$commands = [];
	$controller = new MServerController(
		exePath: 'C:\\Program Files (x86)\\STORMWARE\\POHODA\\Pohoda.exe',
		configName: 'mServer2',
		closeApp: false,
		commandRunner: function (string $cmd) use (&$commands): void {
			$commands[] = $cmd;
		},
	);

	$controller->stop();

	Assert::count(1, $commands);
	Assert::contains('/HTTP stop', $commands[0]);
	Assert::contains('mServer2', $commands[0]);
});


test('stop() with closeApp also closes the Pohoda GUI gracefully', function () {
	$commands = [];
	$controller = new MServerController(
		exePath: 'C:\\Program Files (x86)\\STORMWARE\\POHODA\\Pohoda.exe',
		configName: 'mServer2',
		closeApp: true,
		commandRunner: function (string $cmd) use (&$commands): void {
			$commands[] = $cmd;
		},
	);

	$controller->stop();

	Assert::count(2, $commands);
	Assert::contains('/HTTP stop', $commands[0]);
	Assert::contains('taskkill', $commands[1]);
	Assert::contains('"Pohoda.exe"', $commands[1]);
	// Graceful close — never force-kill (MDB corruption risk).
	Assert::notContains('/F', $commands[1]);
});
