<?php
/**
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\AiBase\Test;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;

class AiBaseServiceTest extends ItopDataTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('/env-production/itomig-ai-base/vendor/autoload.php');
	}

	public function testAssertTrueTrue()
	{
		$this->assertTrue(false);
	}
}