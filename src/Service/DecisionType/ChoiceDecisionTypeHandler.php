<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Service\DecisionType;

use Itomig\iTop\Extension\AIBase\Exception\AIResponseException;
use LLPhant\Classification\Answer;
use LLPhant\Classification\ChoiceAnswer;

final class ChoiceDecisionTypeHandler extends AbstractDecisionTypeHandler
{
	public function GetType(): string
	{
		return 'choice';
	}

	public function ValidateQuestion(array $aQuestion): void
	{
		$this->ValidateInstructions($aQuestion);
		$aCriteria = $aQuestion['criteria'] ?? null;
		if (!is_array($aCriteria) || $aCriteria === [] || count($aCriteria) > 255) {
			throw new \InvalidArgumentException('Choice criteria must contain between 1 and 255 options.');
		}

		foreach ($aCriteria as $sOption => $sDescription) {
			if (trim((string) $sOption) === '' || !is_string($sDescription) || trim($sDescription) === '') {
				throw new \InvalidArgumentException('Choice option names and descriptions must be non-empty strings.');
			}
		}
	}

	public function ValidateAnswer(array $aQuestion, Answer $oAnswer): void
	{
		if (!($oAnswer instanceof ChoiceAnswer)) {
			throw new AIResponseException('The decision provider returned an invalid Choice answer.');
		}
	}
}
