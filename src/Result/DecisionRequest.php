<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Result;

final class DecisionRequest
{
	/**
	 * @param string|array<array-key, mixed> $mState
	 * @param array<string, array<string, mixed>> $aQuestions
	 */
	public function __construct(
		public readonly string|array $mState,
		public readonly array $aQuestions,
	) {
	}
}
