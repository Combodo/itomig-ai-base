<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Service\DecisionType;

use Itomig\iTop\Extension\AIBase\Exception\AIResponseException;
use LLPhant\Classification\Answer;
use LLPhant\Classification\ScoreAnswer;

final class ScoreDecisionTypeHandler extends AbstractDecisionTypeHandler
{
	public function GetType(): string
	{
		return 'score';
	}

	public function ValidateQuestion(array $aQuestion): void
	{
		$this->ValidateInstructions($aQuestion);
		$aCriteria = $aQuestion['criteria'] ?? null;
		if (!is_array($aCriteria) || !array_is_list($aCriteria) || $aCriteria === [] || count($aCriteria) > 10) {
			throw new \InvalidArgumentException('Score criteria must contain between 1 and 10 ordered levels.');
		}

		foreach ($aCriteria as $sDescription) {
			if (!is_string($sDescription) || trim($sDescription) === '') {
				throw new \InvalidArgumentException('Score level descriptions must be non-empty strings.');
			}
		}
	}

	public function ValidateAnswer(array $aQuestion, Answer $oAnswer): void
	{
		if (!($oAnswer instanceof ScoreAnswer)) {
			throw new AIResponseException('The decision provider returned an invalid Score answer.');
		}
	}
}
