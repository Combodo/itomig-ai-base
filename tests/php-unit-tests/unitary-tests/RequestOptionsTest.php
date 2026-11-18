<?php
/**
 * Unit tests for provider-specific request options.
 *
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\AiBase\Test;

use Combodo\iTop\Test\UnitTest\ItopTestCase;
use Itomig\iTop\Extension\AIBase\Engine\AnthropicAIEngine;
use Itomig\iTop\Extension\AIBase\Engine\GenericAIEngine;
use Itomig\iTop\Extension\AIBase\Engine\MistralAIEngine;
use Itomig\iTop\Extension\AIBase\Engine\OllamaAIEngine;
use Itomig\iTop\Extension\AIBase\Engine\OpenAIEngine;
use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;
use OpenAI\Resources\Chat;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Testing\ClientFake;
use ReflectionProperty;

class RequestOptionsTestOpenAIEngine extends OpenAIEngine
{
	private OpenAIChat $oChat;

	private ClientFake $oClient;

	public function __construct(array $requestOptions = [])
	{
		parent::__construct(
			'https://openrouter.ai/api/v1',
			'test-api-key',
			'openai/gpt-4.1',
			false,
			$requestOptions
		);

		$this->oClient = new ClientFake([CreateResponse::fake()]);
		$oConfig = new OpenAIConfig(
			apiKey: 'test-api-key',
			url: 'https://openrouter.ai/api/v1',
			model: 'openai/gpt-4.1',
			client: $this->oClient,
			modelOptions: $requestOptions
		);
		$this->oChat = new OpenAIChat($oConfig);
	}

	protected function createChatInstance(): ChatInterface
	{
		return $this->oChat;
	}

	public function getClient(): ClientFake
	{
		return $this->oClient;
	}
}

class RequestOptionsTest extends ItopTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('/env-production/itomig-ai-base/vendor/autoload.php');
	}

	public function testRequestOptionsDefaultToEmpty(): void
	{
		$aEngines = [
			OpenAIEngine::GetEngine(['api_key' => 'test-api-key']),
			AnthropicAIEngine::GetEngine(['api_key' => 'test-api-key']),
			MistralAIEngine::GetEngine(['api_key' => 'test-api-key']),
			OllamaAIEngine::GetEngine([]),
		];

		foreach ($aEngines as $oEngine) {
			static::assertSame([], $this->getEngineProperty($oEngine, 'requestOptions'));
		}
	}

	public function testConfiguredRequestOptionsArePreservedByEveryEngineFactory(): void
	{
		$aRequestOptions = [
			'tools' => [
				[
					'type' => 'openrouter:web_search',
				],
			],
			'temperature' => 0.2,
		];
		$aEngines = [
			OpenAIEngine::GetEngine([
				'api_key' => 'test-api-key',
				'request_options' => $aRequestOptions,
			]),
			AnthropicAIEngine::GetEngine([
				'api_key' => 'test-api-key',
				'request_options' => $aRequestOptions,
			]),
			MistralAIEngine::GetEngine([
				'api_key' => 'test-api-key',
				'request_options' => $aRequestOptions,
			]),
			OllamaAIEngine::GetEngine([
				'request_options' => $aRequestOptions,
			]),
		];

		foreach ($aEngines as $oEngine) {
			static::assertSame($aRequestOptions, $this->getEngineProperty($oEngine, 'requestOptions'));
		}
	}

	public function testOpenAICompatibleRequestKeepsProviderOptionsAndFunctionInfoTools(): void
	{
		$aRequestOptions = [
			'temperature' => 0.2,
		];
		$oEngine = new RequestOptionsTestOpenAIEngine($aRequestOptions);
		$oToolProvider = new class {
			public function existing_itop_tool(): string
			{
				return 'tool result';
			}
		};
		$oTool = new FunctionInfo(
			'existing_itop_tool',
			$oToolProvider,
			'An existing iTop tool',
			[],
			[]
		);

		$oEngine->GetNextTurn([Message::user('Search for the answer.')], [$oTool]);
		$aRequest = $this->getOpenAIRequest($oEngine->getClient());

		static::assertSame($aRequestOptions['temperature'], $aRequest['temperature']);
		static::assertCount(1, $aRequest['tools']);
		static::assertSame('existing_itop_tool', $aRequest['tools'][0]['function']['name']);
	}

	public function testOpenAIRequestWithoutRequestOptionsRetainsExistingShape(): void
	{
		$oEngine = new RequestOptionsTestOpenAIEngine();

		$oEngine->GetNextTurn([Message::user('Hello.')]);
		$aRequest = $this->getOpenAIRequest($oEngine->getClient());

		static::assertSame('openai/gpt-4.1', $aRequest['model']);
		static::assertArrayHasKey('messages', $aRequest);
		static::assertArrayNotHasKey('tools', $aRequest);
	}

	public function testOpenAIEngineDoesNotDetectProviderFromConfiguredUrl(): void
	{
		$aRequestOptions = ['tools' => [['type' => 'openrouter:web_search']]];
		$oEngine = OpenAIEngine::GetEngine([
			'url' => 'https://openrouter.ai/api/v1',
			'api_key' => 'test-api-key',
			'model' => 'openai/gpt-4.1',
			'request_options' => $aRequestOptions,
		]);

		static::assertSame(OpenAIEngine::class, get_class($oEngine));
		static::assertSame('https://openrouter.ai/api/v1', $this->getEngineProperty($oEngine, 'url'));
		static::assertSame($aRequestOptions, $this->getEngineProperty($oEngine, 'requestOptions'));
	}

	private function getOpenAIRequest(ClientFake $oClient): array
	{
		$aRequest = null;
		$oClient->assertSent(Chat::class, function (string $sMethod, array $aParameters) use (&$aRequest): bool {
			if ($sMethod !== 'create') {
				return false;
			}

			$aRequest = $aParameters;
			return true;
		});

		if (!is_array($aRequest)) {
			static::fail('No OpenAI chat request was recorded.');
		}

		return $aRequest;
	}

	private function getEngineProperty(object $oEngine, string $sPropertyName): mixed
	{
		$oProperty = new ReflectionProperty(GenericAIEngine::class, $sPropertyName);
		$oProperty->setAccessible(true);

		return $oProperty->getValue($oEngine);
	}
}