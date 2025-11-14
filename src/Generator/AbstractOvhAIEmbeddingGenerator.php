<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Generator;

use Exception;
use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use Itomig\iTop\Extension\AIBase\Config\OvhAIConfig;
use Itomig\iTop\Extension\AIBase\Exception\NotImplementedException;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Exception\MissingParameterException;
use LLPhant\OpenAIConfig;
use OpenAI;
use OpenAI\Contracts\ClientContract;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;


abstract class AbstractOvhAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
	public ClientContract $client;

	public int $batch_size_limit = 50;

	public string $apiKey;

	protected string $uri = 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1';

	private readonly StreamFactoryInterface
	&RequestFactoryInterface $factory;

	/**
	 * @throws Exception
	 */
	public function __construct(
		OvhAIConfig $config = new OvhAIConfig(),
		?RequestFactoryInterface $requestFactory = null,
		?StreamFactoryInterface $streamFactory = null,
	) {
		if (! $config->apiKey) {
			throw new MissingParameterException('You have to provide an api key.');
		}
		$this->apiKey = $config->apiKey;

		if (! $config->url) {
			throw new MissingParameterException('You have to provide an url.');
		}
		$this->uri = $config->url.'/embeddings';

		if ($config->client instanceof ClientContract) {
			$this->client = $config->client;
		} else {
			$this->client = OpenAI::factory()
				->withApiKey($this->apiKey)
				->withBaseUri($config->url)
				->make();
		}

		$this->factory = new Psr17Factory(
			requestFactory: $requestFactory,
			streamFactory: $streamFactory,
		);
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


	public function embedDocuments(array $documents): array
	{
		$clientForBatch = $this->createClientForBatch();

		$texts = array_map('LLPhant\Embeddings\DocumentUtils::getUtf8Data', $documents);

		// We create batches of 50 texts to avoid hitting the limit
		if ($this->batch_size_limit <= 0) {
			throw new Exception('Batch size limit must be greater than 0.');
		}

		$chunks = array_chunk($texts, $this->batch_size_limit);

		foreach ($chunks as $chunkKey => $chunk) {
			$body = [
				'model' => $this->getModelName(),
				'input' => $chunk,
			];

			$request = $this->factory->createRequest('POST', $this->uri)
				->withHeader('Content-Type', 'application/json')
				->withHeader('Accept', 'application/json')
				->withHeader('Authorization', 'Bearer '.$this->apiKey)
				->withBody($this->factory->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
			$response = $clientForBatch->sendRequest($request);
			$jsonResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

			if (\array_key_exists('data', $jsonResponse)) {
				foreach ($jsonResponse['data'] as $key => $oneEmbeddingObject) {
					$documents[$chunkKey * $this->batch_size_limit + $key]->embedding = $oneEmbeddingObject['embedding'];
				}
			}
		}

		return $documents;
	}

	abstract public function getEmbeddingLength(): int;

	protected function createClientForBatch(): ClientInterface
	{
		if ($this->apiKey === '' || $this->apiKey === '0') {
			throw new Exception('You have to provide an $apiKey to batch embeddings.');
		}

		return Psr18ClientDiscovery::find();
	}

}