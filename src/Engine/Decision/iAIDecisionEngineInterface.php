<?php

declare(strict_types=1);

namespace Itomig\iTop\Extension\AIBase\Engine\Decision;

use Itomig\iTop\Extension\AIBase\Request\DecisionRequest;
use Itomig\iTop\Extension\AIBase\Result\Decision\DecisionResponse;

interface iAIDecisionEngineInterface
{
    /**
     * Returns the decision engine name.
     */
    public static function GetEngineName(): string;

    /**
     * Creates a configured decision engine.
     *
     * @param array $configuration Engine configuration
     */
    public static function GetEngine(
        array $configuration
    ): iAIDecisionEngineInterface;

    /**
     * Executes a decision request.
     *
     * @param DecisionRequest $oRequest
     * @return DecisionResponse LLPhant answers and provider metadata.
     */
    public function Decide(
        DecisionRequest $oRequest
    ): DecisionResponse;
}