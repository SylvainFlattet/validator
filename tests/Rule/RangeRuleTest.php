<?php

declare(strict_types = 1);

namespace Elie\Validator\Rule;

use PHPUnit\Framework\TestCase;

class RangeRuleTest extends TestCase
{

    /**
     * @dataProvider getRangeValueProvider
     */
    public function testValidate($value, $params, $expectedResult, $expectedError): void
    {
        $rule = new RangeRule('value', $value, $params);

        $res = $rule->validate();

        assertThat($res, identicalTo($expectedResult));

        assertThat($rule->getError(), identicalTo($expectedError));
    }

    public function getRangeValueProvider(): \Generator
    {
        yield 'Given value could be empty' => [
            '', [], RuleInterface::VALID, ''
        ];

        yield 'Given value foo should be valid' => [
            'foo', ['range' => ['foo', 'bar']], RuleInterface::VALID, ''
        ];

        yield 'Given value foot should not be valid' => [
            'foot', ['range' => ['foo', 'bar']], RuleInterface::ERROR, 'value: foot is out of range'
        ];

        yield 'Given value false should not be valid' => [
            '0', ['range' => [0, 'false']], RuleInterface::ERROR, 'value: 0 is out of range'
        ];
    }
}