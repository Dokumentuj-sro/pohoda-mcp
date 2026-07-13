<?php declare(strict_types=1);

namespace DG\Pohoda;


/**
 * Controls the local Pohoda mServer process via `pohoda.exe /HTTP` commands.
 * Windows-only; mServer runs only as part of Pohoda, which is a Windows app.
 */
final class MServerController
{
	/** @var \Closure(string): void */
	private \Closure $commandRunner;


	/**
	 * @param bool $closeApp also close the Pohoda.exe GUI after stopping the
	 *                       listener (full-kill teardown); graceful close only —
	 *                       a force-kill risks MDB corruption
	 * @param \Closure(string): void|null $commandRunner injectable command seam
	 *                       so tests can record commands without a real pohoda.exe
	 */
	public function __construct(
		private readonly string $exePath,
		private readonly string $configName,
		private readonly bool $closeApp = false,
		?\Closure $commandRunner = null,
	) {
		$this->commandRunner = $commandRunner ?? $this->defaultCommandRunner();
	}


	/**
	 * Launches the mServer (non-blocking) and polls `$client->getStatus()` once
	 * per second until it responds or the timeout elapses.
	 *
	 * @throws \RuntimeException on launch failure or when the mServer does not
	 *                           come up within $timeoutSeconds
	 */
	public function start(PohodaClient $client, int $timeoutSeconds = 30): void
	{
		$this->run('start');

		for ($i = 0; $i < $timeoutSeconds; $i++) {
			sleep(1);
			try {
				$client->getStatus();
				return;
			} catch (\RuntimeException) {
				// mServer not ready yet, keep polling
			}
		}

		throw new \RuntimeException(sprintf(
			"mServer '%s' did not start within %d seconds.",
			$this->configName,
			$timeoutSeconds,
		));
	}


	/**
	 * Sends a stop command to the mServer (non-blocking, fire-and-forget), and
	 * with $closeApp also closes the Pohoda.exe GUI afterwards so nothing stays
	 * resident between jobs. Does not wait for the process to terminate.
	 *
	 * @throws \RuntimeException on launch failure of pohoda.exe
	 */
	public function stop(): void
	{
		$this->run('stop');

		if ($this->closeApp) {
			// Graceful close — deliberately no /F: a force-kill can corrupt
			// the company MDB; Pohoda must flush and exit on its own.
			$exe = preg_replace('~^.*[\\\\/]~', '', $this->exePath);
			($this->commandRunner)(sprintf('taskkill /IM "%s"', $exe));
		}
	}


	/**
	 * Runs `pohoda.exe /HTTP <cmd> "<configName>"` via Windows `start /B` so
	 * that PHP does not block on the child's stdout handle.
	 */
	private function run(string $httpCommand): void
	{
		($this->commandRunner)(sprintf(
			'start "" /B "%s" /HTTP %s "%s"',
			$this->exePath,
			$httpCommand,
			$this->configName,
		));
	}


	private function defaultCommandRunner(): \Closure
	{
		return function (string $cmd): void {
			$pipe = popen($cmd, 'r') ?: throw new \RuntimeException('Failed to execute: ' . $cmd);
			pclose($pipe);
		};
	}
}
