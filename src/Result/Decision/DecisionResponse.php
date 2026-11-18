<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Result\Decision;

use LLPhant\Classification\Answer;

final class DecisionResponse
{
	/**
	 * @param array<array-key, Answer> $aAnswers
	 * @param array<string, mixed> $aMetadata Optional provider and usage metadata.
	 */
	public function __construct(
		public readonly array $aAnswers,
		public readonly array $aMetadata = [],
	) {
	}
}
