<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Engine\Decision;

use Itomig\iTop\Extension\AIBase\Exception\AIResponseException;
use Itomig\iTop\Extension\AIBase\Request\DecisionRequest;
use Itomig\iTop\Extension\AIBase\Result\Decision\DecisionResponse;
use LLPhant\Classification\Answer;

abstract class GenericDecisionEngine implements iAIDecisionEngineInterface
{
	protected function __construct(
		protected string $sProvider,
		protected string $sModel
	) {
	}

	final public function Decide(DecisionRequest $oRequest): DecisionResponse
	{
		$aProviderResponse = $this->GetAnswers($oRequest->mState, $oRequest->aQuestions);
		$mProviderAnswers = $aProviderResponse['answers'] ?? null;
		$mProviderMetadata = $aProviderResponse['metadata'] ?? [];
		if (!is_array($mProviderAnswers) || !is_array($mProviderMetadata)) {
			throw new AIResponseException('The decision provider returned an invalid response.');
		}
		$aAnswers = [];
		foreach ($mProviderAnswers as $mQuestionId => $oAnswer) {
			if (!($oAnswer instanceof Answer)) {
				throw new AIResponseException('The decision provider returned an invalid answer.');
			}
			$aAnswers[$mQuestionId] = $oAnswer;
		}

		$aProviderMetadata = [];
		foreach ($mProviderMetadata as $mMetadataKey => $mMetadataValue) {
			if (!is_string($mMetadataKey)) {
				throw new AIResponseException('The decision provider returned invalid metadata.');
			}
			$aProviderMetadata[$mMetadataKey] = $mMetadataValue;
		}

		$aMetadata = array_merge([
			'provider' => $this->sProvider,
			'model' => $this->sModel,
		], $aProviderMetadata);

		return new DecisionResponse($aAnswers, $aMetadata);
	}

	/**
	 * @param array<string, array<string, mixed>> $aQuestions
	 * @return array<string, mixed>
	 */
	abstract protected function GetAnswers(string|array $mState, array $aQuestions): array;
}
