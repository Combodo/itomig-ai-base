<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Service\DecisionType;

use LLPhant\Classification\Answer;

interface iAIDecisionTypeHandlerInterface
{
	public function GetType(): string;

	/**
	 * @param array<string, mixed> $aQuestion
	 */
	public function ValidateQuestion(array $aQuestion): void;

	/**
	 * @param array<string, mixed> $aQuestion
	 */
	public function ValidateAnswer(array $aQuestion, Answer $oAnswer): void;
}
