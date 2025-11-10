<?php
/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Itomig\iTop\Extension\AIBase\Generator;


class OvhAIBGEM3EmbeddingGenerator extends AbstractOvhAIEmbeddingGenerator
{

	public function getEmbeddingLength(): int
	{
		return 1024;
	}

	public function getModelName(): string {
		return 'BGE-M3';
	}


}