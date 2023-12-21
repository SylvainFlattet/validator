<?php

declare(strict_types = 1);

namespace Elie\Validator\Rule;

use PHPUnit\Framework\TestCase;

class StringCleanerRuleTest extends TestCase
{

    /**
     * @dataProvider getStringValueProvider
     */
    public function testValidate($value, $params, $expectedValue, $expectedError): void
    {
        $rule = new StringCleanerRule('name', $value, $params);

        $rule->validate();

        assertThat($rule->getValue(), identicalTo($expectedValue));

        assertThat($rule->getError(), identicalTo($expectedError));
    }

    static public function getStringValueProvider(): \Generator
    {
        yield 'Given value is cleaned by trim and would be valid' => [
            "\x00",
            [],
            '',
            '',
        ];

        yield 'Given value is not cleaned by trim and would be valid even when cleaned is empty' => [
            "\x00",
            [StringCleanerRule::TRIM => false, StringCleanerRule::REQUIRED => true],
            '',
            '',
        ];

        yield 'Given value should be cleaned' => [
            "f\x00f",
            [StringCleanerRule::REQUIRED => true],
            'ff',
            '',
        ];

        yield 'Given value between 4 and 8 characters' => [
            '%7FPeter ',
            [StringCleanerRule::MIN => 4, StringCleanerRule::MAX => 8],
            'Peter',
            '',
        ];

        yield 'Given value should not be cleaned before validation' => [
            "f\x00f",
            [StringCleanerRule::REQUIRED => true, StringCleanerRule::MAX => 2],
            "f\x00f",
            "name: The length of f\x00f is not between 0 and 2",
        ];
    }
}
