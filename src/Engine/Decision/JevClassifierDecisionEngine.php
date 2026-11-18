<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Engine\Decision;

use LLPhant\Classification\ChoiceType;
use LLPhant\Classification\JevClassifier;
use LLPhant\Classification\JevConfig;
use LLPhant\Classification\NoulCriteria;
use LLPhant\Classification\NoulType;
use LLPhant\Classification\ScoreType;

final class JevClassifierDecisionEngine extends GenericDecisionEngine
{
	private JevClassifier $oClassifier;

	public function __construct(JevClassifier $oClassifier, string $sModel, string $sUrl)
	{
		$vHost = parse_url($sUrl, PHP_URL_HOST);
		parent::__construct(is_string($vHost) && $vHost !== '' ? $vHost : 'Jev', $sModel);
		$this->oClassifier = $oClassifier;
	}

	public static function GetEngineName(): string
	{
		return 'JevClassifier';
	}

	public static function GetEngine(array $configuration): iAIDecisionEngineInterface
	{
		$sApiKey = trim((string) ($configuration['api_key'] ?? ''));
		$sUrl = trim((string) ($configuration['url'] ?? JevConfig::URL));
		$sModel = trim((string) ($configuration['model'] ?? JevConfig::LATEST));
		$sUrl = self::NormalizeEndpoint($sUrl === '' ? JevConfig::URL : $sUrl);
		$sModel = $sModel === '' ? JevConfig::LATEST : $sModel;

		$oClassifier = new JevClassifier(new JevConfig(
			apiKey: $sApiKey === '' ? null : $sApiKey,
			url: $sUrl,
			model: $sModel
		));

		return new self($oClassifier, $sModel, $sUrl);
	}

	protected function GetAnswers(string|array $mState, array $aQuestions): array
	{
		$sState = is_string($mState)
			? $mState
			: json_encode($mState, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$aJevQuestions = [];
		foreach ($aQuestions as $sQuestionId => $aQuestion) {
			$aJevQuestions[$sQuestionId] = match ($aQuestion['type']) {
				'noul' => new NoulType(
					$aQuestion['instructions'],
					isset($aQuestion['criteria'])
						? new NoulCriteria($aQuestion['criteria']['true'], $aQuestion['criteria']['false'])
						: null
				),
				'choice' => new ChoiceType($aQuestion['instructions'], $aQuestion['criteria']),
				'score' => new ScoreType($aQuestion['instructions'], $aQuestion['criteria']),
				default => throw new \InvalidArgumentException('Unsupported Jev question type.'),
			};
		}

		$aJevAnswers = $this->oClassifier->askQuestions($sState, $aJevQuestions);
		$aUsage = [];
		foreach ($aJevAnswers as $oAnswer) {
			$aUsage = [
				'input_tokens' => $oAnswer->inputTokens,
				'output_tokens' => $oAnswer->outputTokens,
			];
			break;
		}

		return [
			'answers' => $aJevAnswers,
			'metadata' => ['usage' => $aUsage],
		];
	}

	private static function NormalizeEndpoint(string $sUrl): string
	{
		$sUrl = rtrim($sUrl, '/');
		if (str_ends_with($sUrl, '/v1/systemone')) {
			return $sUrl;
		}
		if (str_ends_with($sUrl, '/v1')) {
			return $sUrl.'/systemone';
		}

		return $sUrl.'/v1/systemone';
	}
}
