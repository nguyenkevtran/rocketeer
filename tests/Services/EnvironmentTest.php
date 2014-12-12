<?php
namespace Rocketeer\Services;

use Rocketeer\TestCases\RocketeerTestCase;

class EnvironmentTest extends RocketeerTestCase
{
	public function testCanGetLineEndings()
	{
		$this->localStorage->destroy();

		$this->assertEquals(PHP_EOL, $this->environment->getLineEndings());
	}

	public function testCanGetSeparators()
	{
		$this->localStorage->destroy();

		$this->assertEquals(DIRECTORY_SEPARATOR, $this->environment->getSeparator());
	}

	public function testCanGetOperatingSystem()
	{
		$this->localStorage->destroy();

		$this->assertEquals(PHP_OS, $this->environment->getOperatingSystem());
	}
}
