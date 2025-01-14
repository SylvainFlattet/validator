<?php

declare(strict_types = 1);

namespace Elie\Validator;

use Elie\Validator\Rule\ArrayRule;
use Elie\Validator\Rule\BooleanRule;
use Elie\Validator\Rule\CollectionRule;
use Elie\Validator\Rule\EmailRule;
use Elie\Validator\Rule\JsonRule;
use Elie\Validator\Rule\MatchRule;
use Elie\Validator\Rule\MultipleAndRule;
use Elie\Validator\Rule\MultipleOrRule;
use Elie\Validator\Rule\NumericRule;
use Elie\Validator\Rule\RangeRule;
use Elie\Validator\Rule\StringRule;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{

    public function testValidatedContext(): void
    {
        $validator = new Validator(['name' => 'Ben ']);

        $rule = ['name', StringRule::class, 'min' => 3, 'max' => 30];

        $validator->setRules([$rule]);

        $res = $validator->validate();
        assertThat($res, is(true));

        $validatedContext = $validator->getValidatedContext();
        assertThat($validatedContext, anArray(['name' => 'Ben']));

        $value = $validator->get('name');
        assertThat($value, identicalTo('Ben '));

        $value = $validator->get('age');
        assertThat($value, nullValue());

        $rules = $validator->getRules();
        assertThat($rules[0], anArray($rule));

        assertThat($validator->shouldStopOnError(), is(false));
    }

    public function testValidatorStopOnError(): void
    {
        $validator = new Validator(['name' => 'Ben '], [], true);

        $validator->setRules([
            ['name', StringRule::class, 'min' => 4, 'max' => 12],
        ]);

        $res = $validator->validate();
        assertThat($res, is(false));

        // value should not exist on error
        $validatedContext = $validator->getValidatedContext();
        assertThat($validatedContext, emptyArray());

        assertThat($validator->shouldStopOnError(), is(true));
    }

    public function testValidatorWithJsonPartialValidation(): void
    {
        $rules = [
            ['email', EmailRule::class, EmailRule::REQUIRED => true],
            ['user', JsonRule::class, JsonRule::REQUIRED => true, JsonRule::DECODE => true],
        ];

        $data = [
            'email' => 'elie29@gmail.com',
            'user' => '{"name": "John", "age": 25}',
        ];

        $validator = new Validator($data, $rules);

        assertThat($validator->validate(), is(true));

        // In order to validate users json context
        $validator->setRules([
            ['name', MatchRule::class, MatchRule::PATTERN => '/^[a-z]{1,20}$/i'],
            ['age', NumericRule::class, NumericRule::MAX => 80],
        ]);

        $user = $validator->getValidatedContext()['user'];
        $validator->setContext($user);

        // Validate and merge context
        assertThat($validator->validate(true), is(true));

        $data = $validator->getValidatedContext();
        assertThat($data, hasEntry('age', 25));
        assertThat($data, hasKey('user'));
    }

    public function testValidatorWithRawPartialValidation(): void
    {
        $rules = [
            ['email', EmailRule::class, EmailRule::REQUIRED => true],
            ['tags', ArrayRule::class, ArrayRule::REQUIRED => true],
        ];

        $data = [
            'email' => 'elie29@gmail.com',
            'tags' => [
                ['code' => 12, 'slug' => 'one'],
                ['code' => 13, 'slug' => 'two'],
                ['code' => 15, 'slug' => 'three'],
            ],
        ];

        $validator = new Validator($data, $rules);

        assertThat($validator->validate(), is(true));

        // In order to validate tags array context
        $validator->setRules([
            ['code', NumericRule::class, NumericRule::MAX => 80],
            ['slug', MatchRule::class, MatchRule::PATTERN => '/^[a-z]+$/i'],
        ]);

        $tags = $validator->getValidatedContext()['tags'];
        $data = [];
        foreach ($tags as $tag) {
            $validator->setContext($tag);
            assertThat($validator->validate(), is(true));
            $data[] = $validator->getValidatedContext();
        }

        assertThat($data, arrayWithSize(3));
    }

    public function testValidatorWithCollection(): void
    {
        $rules = [
            ['email', EmailRule::class, EmailRule::REQUIRED => true],
            ['tags', CollectionRule::class, CollectionRule::RULES => [
                ['code', NumericRule::class, NumericRule::MAX => 80],
                ['slug', MatchRule::class, MatchRule::PATTERN => '/^[a-z]{1,5}$/i'],
            ]],
        ];

        $data = [
            'email' => 'elie29@gmail.com',
            'tags' => [
                ['code' => 12, 'slug' => 'one'],
                ['code' => 13, 'slug' => 'two'],
                ['code' => 15, 'slug' => 'three'],
            ],
        ];

        $validator = new Validator($data, $rules);

        assertThat($validator->validate(), is(true));

        $tags = $validator->getValidatedContext()['tags'];

        assertThat($tags, arrayWithSize(3));
    }

    public function testValidatorGetImplodedErrors(): void
    {
        $validator = new Validator(['name' => 'Ben'], [
            ['name', NumericRule::class],
            ['name', ArrayRule::class],
            ['name', BooleanRule::class],
        ]);

        $res = $validator->validate();
        assertThat($res, is(false));

        // value should not exist on error
        $validatedContext = $validator->getValidatedContext();
        assertThat($validatedContext, emptyArray());

        $expected = 'name: Ben is not numeric,name does not have an array value: Ben,name: Ben is not a valid boolean';
        assertThat($validator->getImplodedErrors(','), is($expected));
    }

    public function testExistingKeysOnlyShouldBeAppendToTheValidatedContext(): void
    {
        $validator = new Validator(
            // Context
            ['name' => 'John', 'address' => null],
            // Rules
            [
                ['name', StringRule::class],
                ['address', StringRule::class],
                ['age', NumericRule::class],
            ]
        );

        $validator->appendExistingItemsOnly(true);

        $res = $validator->validate();
        assertThat($res, is(true));

        $validatedContext = $validator->getValidatedContext();

        assertThat($validatedContext, hasKey('name'));
        assertThat($validatedContext, hasKey('address'));
        assertThat($validatedContext, not(hasKey('age')));
    }

    /**
     * @dataProvider getValidatorProvider
     */
    public function testValidate($context, $rules, $expectedResult, $errorsSize): void
    {
        $validator = new Validator($context);
        $validator->setRules($rules);

        $res = $validator->validate();

        assertThat($res, identicalTo($expectedResult));
        assertThat($validator->getErrors(), arrayWithSize($errorsSize));
    }

    public static function getValidatorProvider(): \Generator
    {
        yield 'Age and name are valid' => [
            // context
            [
                'age' => 25,
                'name' => 'Ben',
            ],
            // rules
            [
                ['age', NumericRule::class, 'min' => 5, 'max' => 65],
                ['name', StringRule::class, 'min' => 3, 'max' => 30],
            ],
            // expectedResult
            true,
            // errorsSize
            0,
        ];

        yield 'Validate with multiple and rule' => [
            [
                'age' => 25,
            ],
            [
                ['age', MultipleAndRule::class, MultipleAndRule::REQUIRED => true, MultipleAndRule::RULES => [
                    [NumericRule::class, NumericRule::MIN => 14],
                    [RangeRule::class, RangeRule::RANGE => [25, 26]],
                ]],
            ],
            true,
            0,
        ];

        yield 'Validate with multiple or rule' => [
            [
                'foo' => 'bar',
            ],
            [
                ['foo', MultipleOrRule::class, MultipleOrRule::REQUIRED => true, MultipleOrRule::RULES => [
                    [NumericRule::class, NumericRule::MIN => 14],
                    [StringRule::class, StringRule::MIN => 1],
                ]],
            ],
            true,
            0,
        ];

        yield 'Age is not valid' => [
            [
                'age' => 25,
            ],
            [
                ['age', NumericRule::class, 'min' => 26, 'max' => 65],
            ],
            false,
            1,
        ];
        yield 'Key with numeric value' => [
            [
                0 => 25,
                1 => 'Test',
            ],
            [
                [0, NumericRule::class, 'min' => 22, 'max' => 65],
                [1, StringRule::class, 'min' => 0, 'max' => 10],
            ],
            true,
            0,
        ];
        yield 'Key with index context' => [
            [
                28,
                'Test2',
            ],
            [
                [0, NumericRule::class, 'min' => 22, 'max' => 65],
                [1, StringRule::class, 'min' => 0, 'max' => 10],
            ],
            true,
            0,
        ];
    }
}
