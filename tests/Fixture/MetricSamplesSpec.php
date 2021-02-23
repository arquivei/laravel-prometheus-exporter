<?php

declare(strict_types=1);

namespace Arquivei\LaravelPrometheusExporter\Tests\Fixture;

use Prometheus\MetricFamilySamples;

class MetricSamplesSpec
{
    private $name = null;
    private $type = null;
    private $help = null;
    private $labelNames = null;

    public static function create(): self
    {
        return new self();
    }

    private function __construct()
    {
    }

    public function withName(string $name): self
    {
        $spec = clone $this;
        $spec->name = $name;
        return $spec;
    }

    public function withType(string $type): self
    {
        $spec = clone $this;
        $spec->type = $type;
        return $spec;
    }

    public function withHelp(string $help): self
    {
        $spec = clone $this;
        $spec->help = $help;
        return $spec;
    }

    public function withLabelNames(array $labelNames): self
    {
        $spec = clone $this;
        $spec->labelNames = $labelNames;
        return $spec;
    }

    public function matches(MetricFamilySamples $samples): bool
    {
        if (
            !is_null($this->name)
            && $samples->getName() !== $this->name
        ) {
            return false;
        }

        if (
            !is_null($this->type)
            && $samples->getType() !== $this->type
        ) {
            return false;
        }

        if (
            !is_null($this->help)
            && $samples->getHelp() !== $this->help
        ) {
            return false;
        }

        if (
            !is_null($this->labelNames)
            && !$this->arraysMatch($this->labelNames, $samples->getLabelNames())
        ) {
            return false;
        }

        return true;
    }

    private function arraysMatch(array $first, array $second): bool
    {
        sort($first);
        sort($second);

        return $first === $second;
    }
}
