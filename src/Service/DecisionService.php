<?php

namespace Itomig\iTop\Extension\AIBase\Service;

use Itomig\iTop\Extension\AIBase\Exception\AIResponseException;
use Itomig\iTop\Extension\AIBase\Engine\Decision\iAIDecisionEngineInterface;
use Itomig\iTop\Extension\AIBase\Request\DecisionRequest;
use Itomig\iTop\Extension\AIBase\Result\Decision\DecisionResponse;
use Itomig\iTop\Extension\AIBase\Service\DecisionType\ChoiceDecisionTypeHandler;
use Itomig\iTop\Extension\AIBase\Service\DecisionType\iAIDecisionTypeHandlerInterface;
use Itomig\iTop\Extension\AIBase\Service\DecisionType\NoulDecisionTypeHandler;
use Itomig\iTop\Extension\AIBase\Service\DecisionType\ScoreDecisionTypeHandler;
use LLPhant\Classification\Answer;
use LLPhant\Classification\ChoiceAnswer;
use LLPhant\Classification\NoulAnswer;
use LLPhant\Classification\ScoreAnswer;

class DecisionService
{
	private iAIDecisionEngineInterface $oDecisionEngine;

	/**
	 * @var array<string, iAIDecisionTypeHandlerInterface>
	 */
	private array $aDecisionTypeHandlers;

	public function __construct(iAIDecisionEngineInterface $oDecisionEngine)
	{
		$this->oDecisionEngine = $oDecisionEngine;
		$this->aDecisionTypeHandlers = [];
		foreach ([
			new NoulDecisionTypeHandler(),
			new ChoiceDecisionTypeHandler(),
			new ScoreDecisionTypeHandler(),
		] as $oHandler) {
			$this->aDecisionTypeHandlers[$oHandler->GetType()] = $oHandler;
		}
	}

	public function Decide(DecisionRequest $oRequest): DecisionResponse
	{
		$aQuestions = $this->ValidateRequest($oRequest);

		return $this->ExecuteDecision($oRequest, $aQuestions);
	}

	public function DecideNoul(DecisionRequest $oRequest, string $sQuestionId): NoulAnswer
	{
		$oAnswer = $this->GetAnswerOfType($oRequest, $sQuestionId, 'noul');
		if (!($oAnswer instanceof NoulAnswer)) {
			throw new AIResponseException('The decision response does not contain a Noul answer.');
		}

		return $oAnswer;
	}

	public function DecideChoice(DecisionRequest $oRequest, string $sQuestionId): ChoiceAnswer
	{
		$oAnswer = $this->GetAnswerOfType($oRequest, $sQuestionId, 'choice');
		if (!($oAnswer instanceof ChoiceAnswer)) {
			throw new AIResponseException('The decision response does not contain a Choice answer.');
		}

		return $oAnswer;
	}

	public function DecideScore(DecisionRequest $oRequest, string $sQuestionId): ScoreAnswer
	{
		$oAnswer = $this->GetAnswerOfType($oRequest, $sQuestionId, 'score');
		if (!($oAnswer instanceof ScoreAnswer)) {
			throw new AIResponseException('The decision response does not contain a Score answer.');
		}

		return $oAnswer;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function ValidateRequest(DecisionRequest $oRequest): array
	{
		try {
			json_encode($oRequest->mState, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		} catch (\JsonException $oException) {
			throw new \InvalidArgumentException('Decision state must be JSON-serializable.', 0, $oException);
		}

		if ($oRequest->aQuestions === []) {
			throw new \InvalidArgumentException('A decision request must contain at least one question.');
		}

		$aQuestions = [];
		foreach ($oRequest->aQuestions as $sQuestionId => $aQuestion) {
			$sQuestionId = (string) $sQuestionId;
			if (trim($sQuestionId) === '' || !is_array($aQuestion)) {
				throw new \InvalidArgumentException('Decision questions must have a non-empty ID and a question definition.');
			}

			$sType = $aQuestion['type'] ?? null;
			$oHandler = is_string($sType) ? ($this->aDecisionTypeHandlers[$sType] ?? null) : null;
			if (!($oHandler instanceof iAIDecisionTypeHandlerInterface)) {
				throw new \InvalidArgumentException('Unsupported decision question type.');
			}
			$oHandler->ValidateQuestion($aQuestion);
			$aQuestions[$sQuestionId] = $aQuestion;
		}

		return $aQuestions;
	}

	/**
	 * @param array<string, array<string, mixed>> $aQuestions
	 */
	private function ExecuteDecision(DecisionRequest $oRequest, array $aQuestions): DecisionResponse
	{
		$oEngineResponse = $this->oDecisionEngine->Decide($oRequest);
		$aExpectedIds = array_keys($aQuestions);
		$aEngineAnswers = $oEngineResponse->aAnswers;
		if (count($aEngineAnswers) !== count($aExpectedIds)
			|| array_diff($aExpectedIds, array_keys($aEngineAnswers)) !== []
			|| array_diff(array_keys($aEngineAnswers), $aExpectedIds) !== []) {
			throw new AIResponseException('The decision provider returned answers that do not match the requested questions.');
		}

		foreach ($aQuestions as $sQuestionId => $aQuestion) {
			$oAnswer = $aEngineAnswers[$sQuestionId] ?? null;
			if (!($oAnswer instanceof Answer)) {
				throw new AIResponseException('The decision provider returned an invalid answer.');
			}

			$oHandler = $this->aDecisionTypeHandlers[$aQuestion['type']];
			$oHandler->ValidateAnswer($aQuestion, $oAnswer);
		}

		return $oEngineResponse;
	}

	private function GetAnswerOfType(
		DecisionRequest $oRequest,
		string $sQuestionId,
		string $sExpectedType
	): Answer {
		$aQuestions = $this->ValidateRequest($oRequest);
		if (!isset($aQuestions[$sQuestionId]) || $aQuestions[$sQuestionId]['type'] !== $sExpectedType) {
			throw new \InvalidArgumentException("Question '$sQuestionId' is not of type '$sExpectedType'.");
		}

		return $this->ExecuteDecision($oRequest, $aQuestions)->aAnswers[$sQuestionId];
	}
}
