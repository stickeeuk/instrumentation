<?php

declare(strict_types=1);

namespace Stickee\Instrumentation\Utils;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SamplingResult;

/**
 * This is a decorator for other samplers that changes the decision of SamplingResult::DROP to SamplingResult::RECORD_ONLY
 */
class RecordSampler implements SamplerInterface
{
    /**
     * Constructor
     *
     * @param \OpenTelemetry\SDK\Trace\SamplerInterface $sampler The sampler to decorate
     */
    public function __construct(private readonly SamplerInterface $sampler) {}

    /**
     * Returns `SamplingResult` based on probability. Respects the parent `SampleFlag`
     * {@inheritdoc}
     */
    public function shouldSample(
        ContextInterface $parentContext,
        string $traceId,
        string $spanName,
        int $spanKind,
        AttributesInterface $attributes,
        array $links,
    ): SamplingResult {
        $result = $this->sampler->shouldSample($parentContext, $traceId, $spanName, $spanKind, $attributes, $links);

        if ($result->getDecision() === SamplingResult::DROP) {
            return new SamplingResult(SamplingResult::RECORD_ONLY, $result->getAttributes(), $result->getTraceState());
        }

        return $result;
    }

    /**
     * Get the description
     */
    public function getDescription(): string
    {
        return $this->sampler->getDescription();
    }
}
