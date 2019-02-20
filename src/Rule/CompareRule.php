<?php

declare(strict_types = 1);

namespace Elie\Validator\Rule;

/**
 * This class verifies that a value is valid.
 */
class CompareRule extends AbstractRule
{

    /**
     * Sign value supported ==, ===, !=, !==, <=, >=, <, >.
     * Default sets to ==.
     * @var string
     */
    protected $sign = RuleInterface::EQ;

    /**
     * Expected value.
     * Default sets to null.
     * @var string
     */
    protected $expected = null;

    /**
     * Params could have the following structure:
     * [
     *   'required' => {bool:optional},
     *   'trim' => {bool:optional},
     *   'sign' => {string:optional:EQ by default},
     *   'expected' => {string:optional:null by default}
     * ]
     */
    public function __construct(string $key, $value, array $params = [])
    {
        parent::__construct($key, $value, $params);

        if (isset($params['sign'])) {
            $this->sign = $params['sign'];
        }

        if (isset($params['expected'])) {
            $this->expected = $params['expected'];
        }
    }

    public function validate(): int
    {
        $run = parent::validate();

        if ($run !== RuleInterface::CHECK) {
            return $run;
        }

        $method = $this->sign;
        if (! $this->$method()) {
            $label = self::SIGNS[$this->sign];
            $this->error = "{$this->key}: {$this->value} is not {$label} {$this->expected}";
            return RuleInterface::ERROR;
        }

        return RuleInterface::VALID;
    }

    /**
     * Provided value == expected one.
     *
     * @return bool
     */
    protected function eq()
    {
        return $this->value == $this->expected;
    }

    /**
     * Provided value === expected one.
     *
     * @return bool
     */
    protected function seq()
    {
        return $this->value === $this->expected;
    }

    /**
     * Provided value != expected one.
     *
     * @return bool
     */
    protected function neq()
    {
        return $this->value != $this->expected;
    }

    /**
     * Provided value !== expected one.
     *
     * @return bool
     */
    protected function nseq()
    {
        return $this->value !== $this->expected;
    }

    /**
     * Provided value <= expected one.
     *
     * @return bool
     */
    protected function lte()
    {
        return $this->value <= $this->expected;
    }

    /**
     * Provided value >= expected one.
     *
     * @return bool
     */
    protected function gte()
    {
        return $this->value >= $this->expected;
    }

    /**
     * Provided value < expected one.
     *
     * @return bool
     */
    protected function lt()
    {
        return $this->value < $this->expected;
    }

    /**
     * Provided value > expected one.
     *
     * @return bool
     */
    protected function gt()
    {
        return $this->value > $this->expected;
    }
}