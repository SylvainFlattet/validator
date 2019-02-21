<?php

declare(strict_types = 1);

namespace Elie\Validator\Rule;

/**
 * This class verifies that a value is a valid string.
 */
class StringRule extends AbstractRule
{

    /**
     * Minimun string length.
     */
    protected $min = 0;

    /**
     * Maximum string length.
     */
    protected $max = 0;

    /**
     * Params could have the following structure:
     * [
     *   'required' => {bool:optional},
     *   'trim' => {bool:optional},
     *   'messages' => {array:optional:key/value message patterns},
     *   'min' => {int:optional:0 by default},
     *   'max' => {int:optional:value length by default}
     * ]
     */
    public function __construct(string $key, $value, array $params = [])
    {
        parent::__construct($key, $value, $params);

        if (isset($params[self::MIN])) {
            $this->min = (int) $params[self::MIN];
        }
        if (isset($params[self::MAX])) {
            $this->max = (int) $params[self::MAX];
        }
    }

    public function validate(): int
    {
        $run = parent::validate();

        if ($run !== RuleInterface::CHECK) {
            return $run;
        }

        if (! is_string($this->value)) {
            return $this->setAndReturnError(self::INVALID_STRING);
        }

        return $this->checkMinMax();
    }

    protected function checkMinMax(): int
    {
        $len = strlen($this->value);
        $maxOrLen = $this->max ?: $len;

        if ($len < $this->min || $len > $maxOrLen) {
            return $this->setAndReturnError(self::INVALID_STRING_LENGTH, [
                '%min%' => $this->min,
                '%max%' => $this->max,
            ]);
        }

        return RuleInterface::VALID;
    }
}
