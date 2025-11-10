<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Generator;

class OvhAIBGEBaseEnV15EmbeddingGenerator extends AbstractOvhAIEmbeddingGenerator
{
	public function getEmbeddingLength(): int
	{
		return 768;
	}

	public function getModelName(): string
	{
		return 'bge-base-en-v1.5';
	}
}