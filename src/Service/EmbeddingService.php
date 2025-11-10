<?php

namespace Itomig\iTop\Extension\AIBase\Service;

use Combodo\iTop\Service\InterfaceDiscovery\InterfaceDiscovery;
use Itomig\iTop\Extension\AIBase\Engine\iAIEngineInterface;
use Itomig\iTop\Extension\AIBase\Exception\AIConfigurationException;
use Itomig\iTop\Extension\AIBase\Helper\AIBaseHelper;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use MetaModel;

/**
 * @copyright   Copyright (C) 2010-2025 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class EmbeddingService
{

	protected ?iAIEngineInterface $oAIEngine;

	private EmbeddingGeneratorInterface $embeddingGenerator;

	public function __construct(?iAIEngineInterface $engine = null)
	{
		if(is_null($engine))
		{
			$sAIEngineName = MetaModel::GetModuleSetting(AIBaseHelper::MODULE_CODE, 'ai_engine.name', '');
			try {
				$AIEngineClass = self::GetAIEngineClass($sAIEngineName);
			}
			catch (\ReflectionException $e)
			{
				throw new AIConfigurationException('Unable to find AIEngineClass with name ="'.$sAIEngineName.'"', null, '', $e);
			}
			if(empty($AIEngineClass))
			{
				throw new AIConfigurationException('Unable to find AIEngineClass with name ="'.$sAIEngineName.'"');
			}
			$engine= $AIEngineClass::GetEngine(MetaModel::GetModuleSetting(AIBaseHelper::MODULE_CODE, 'ai_engine.configuration', ''));

		}
		$this->oAIEngine = $engine;
		$this->embeddingGenerator = $this->oAIEngine->GetEmbeddingGenerator();
	}




	/**
	 * Retrieves and returns the class name of the configured AI engine instance, if any.
	 *
	 * @return class-string<iAIEngineInterface>|'' The class name of the AI engine, or null if no engine is configured.
	 * @throws \ReflectionException
	 */
	protected static function GetAIEngineClass(string $sAIEngineName)
	{
		$sDesiredAIEngineClass = '';
		/** @var $aAIEngines */
		$oInterfaceDiscovery = InterfaceDiscovery::GetInstance();
		$aAIEngineClasses = $oInterfaceDiscovery->FindItopClasses(iAIEngineInterface::class);
		/** @var class-string<iAIEngineInterface> $AIEngineClass */
		foreach ($aAIEngineClasses as $sAIEngineClass)
		{
			if ($sAIEngineName === $sAIEngineClass::GetEngineName())
			{
				$sDesiredAIEngineClass = $sAIEngineClass;
				break;
			}
		}
		return $sDesiredAIEngineClass;
	}

	public function GetEmbedding($sMessage) : array
	{
		return $this->embeddingGenerator->embedText($sMessage);
	}
	public function GetEmbeddingLength(): int
	{
		return $this->embeddingGenerator->getEmbeddingLength();

	}

}
