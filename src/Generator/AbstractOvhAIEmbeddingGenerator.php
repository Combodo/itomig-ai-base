<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Generator;

use Exception;
use Itomig\iTop\Extension\AIBase\Config\OvhAIConfig;
use Itomig\iTop\Extension\AIBase\Exception\NotImplementedException;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use OpenAI;
use OpenAI\Contracts\ClientContract;


abstract class AbstractOvhAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
	public ClientContract $client;

	public int $batch_size_limit = 50;

	public string $apiKey;

	protected string $uri = 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1';

	/**
	 * @throws Exception
	 */
	public function __construct(?OvhAIConfig $config = null)
	{
		$client = $config?->client;

		if ($client instanceof ClientContract) {
			$this->client = $client;
		} else {
			$apiKey = $config?->apiKey ?? getenv('OVH_AI_ENDPOINTS_ACCESS_TOKEN');
			if (!$apiKey) {
				throw new Exception('You have to provide a OVH_AI_ENDPOINTS_ACCESS_TOKEN env var to request OvhAI .');
			}

			$url = $config->url ?? (getenv('OPENAI_BASE_URL') ?: 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1');

			$url = rtrim($url, '/').'/';

			$this->client = OpenAI::factory()
				->withApiKey($apiKey)
				->withBaseUri($url)
				->withHttpHeader('Accept', 'application/json')
				->make();

			$this->uri = $url.'/embeddings';
			$this->apiKey = $apiKey;
		}
	}


	// need to upgrade llphant to last version for this to work
	public function embedText(string $text): array
	{
		$textUtf8 = str_replace("\n", ' ', DocumentUtils::toUtf8($text));
		try {
			$response = $this->client->embeddings()->create([
				'model' => $this->getModelName(),
				'input' => [$textUtf8],
			]);
			return $response->embeddings[0]->embedding;
		} catch (\Throwable $e) {
			error_log('Error embedding text: ' . $text . ' - ' . $e->getMessage());
			return [];
		}
	}


	/**
	 * @throws \Itomig\iTop\Extension\AIBase\Exception\NotImplementedException
	 */
	public function embedDocument(Document $document): Document
	{
		throw new NotImplementedException('Not implemented yet');
	}

	/**
	 * @throws \Itomig\iTop\Extension\AIBase\Exception\NotImplementedException
	 */
	public function embedDocuments(array $documents): array
	{
		throw new NotImplementedException('Not implemented yet');
	}

	abstract public function getEmbeddingLength(): int;
}