<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Service\DecisionType;

abstract class AbstractDecisionTypeHandler implements iAIDecisionTypeHandlerInterface
{
	/**
	 * @param array<string, mixed> $aQuestion
	 */
	protected function ValidateInstructions(array $aQuestion): string
	{
		$sInstructions = $aQuestion['instructions'] ?? null;
		if (!is_string($sInstructions)) {
			throw new \InvalidArgumentException('Decision question instructions must be a string.');
		}

		return $sInstructions;
	}

}
