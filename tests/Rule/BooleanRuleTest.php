<?php

declare(strict_types = 1);

namespace Elie\Validator\Rule;

use PHPUnit\Framework\TestCase;

class BooleanRuleTest extends TestCase
{

    /**
     * @dataProvider getBooleanValueProvider
     */
    public function testValidate($value, $expectedResult, $expectedError): void
    {
        $rule = new BooleanRule('name', $value);

        $res = $rule->validate();

        assertThat($res, identicalTo($expectedResult));

        assertThat($rule->getError(), identicalTo($expectedError));
    }

    public function getBooleanValueProvider(): \Generator
    {
        yield 'Given value could be empty' => [
            '', RuleInterface::VALID, ''
        ];

        yield 'Given value could be 1' => [
            '1 ', RuleInterface::VALID, ''
        ];

        yield 'Given value could be true' => [
            true, RuleInterface::VALID, ''
        ];

        yield 'Given value could not be a string' => [
            'test', RuleInterface::ERROR, 'name: test is not a valid boolean'
        ];
    }
}