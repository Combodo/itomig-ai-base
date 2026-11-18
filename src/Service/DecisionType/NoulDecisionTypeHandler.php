<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Service\DecisionType;

use Itomig\iTop\Extension\AIBase\Exception\AIResponseException;
use LLPhant\Classification\Answer;
use LLPhant\Classification\NoulAnswer;

final class NoulDecisionTypeHandler extends AbstractDecisionTypeHandler
{
	public function GetType(): string
	{
		return 'noul';
	}

	public function ValidateQuestion(array $aQuestion): void
	{
		$this->ValidateInstructions($aQuestion);
		$aCriteria = $aQuestion['criteria'] ?? null;
		if ($aCriteria === null) {
			return;
		}
		if (!is_array($aCriteria) || array_diff(['true', 'false'], array_keys($aCriteria)) !== []
			|| array_diff(array_keys($aCriteria), ['true', 'false']) !== []) {
			throw new \InvalidArgumentException('Noul criteria must contain only the true and false descriptions.');
		}

		foreach (['true', 'false'] as $sOutcome) {
			if (!is_string($aCriteria[$sOutcome])) {
				throw new \InvalidArgumentException('Noul true and false criteria must be strings.');
			}
		}
	}

	public function ValidateAnswer(array $aQuestion, Answer $oAnswer): void
	{
		if (!($oAnswer instanceof NoulAnswer)) {
			throw new AIResponseException('The decision provider returned an invalid Noul answer.');
		}
	}
}
