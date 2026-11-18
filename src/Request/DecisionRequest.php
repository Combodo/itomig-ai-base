<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Request;

final class DecisionRequest
{
	/**
	 * @param string|array<array-key, mixed> $mState
	 * @param array<array-key, mixed> $aQuestions
	 */
	public function __construct(
		public readonly string|array $mState,
		public readonly array $aQuestions,
	) {
	}
}
