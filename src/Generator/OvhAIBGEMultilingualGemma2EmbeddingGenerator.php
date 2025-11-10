<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Generator;

class OvhAIBGEMultilingualGemma2EmbeddingGenerator extends AbstractOvhAIEmbeddingGenerator
{
	public function getEmbeddingLength(): int
	{
		return 3584;
	}

	public function getModelName(): string
	{
		return 'bge-multilingual-gemma2';
	}
}