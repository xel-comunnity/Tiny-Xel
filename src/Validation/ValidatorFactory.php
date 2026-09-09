<?php

declare(strict_types=1);

namespace Tiny\Xel\Validation;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use Tiny\Xel\Exception\ValidationException;

/**
 * A ready-to-use Illuminate\Validation\Factory - the same validation engine
 * and rule set as a full Laravel app - with zero setup required: no service
 * providers, no config files. Default English messages are loaded from
 * lang/en/validation.php so out-of-the-box errors read the same as they do
 * in Laravel, not as raw untranslated keys.
 *
 * Business code typically only needs:
 *
 *   $data = ValidatorFactory::validate($input, [
 *       "email" => "required|email",
 *       "name" => "required|string|max:255",
 *   ]);
 *
 * validate() throws Tiny\Xel\Exception\ValidationException on failure,
 * which the framework's global exception handler already renders as a 422
 * with per-field errors - no try/catch needed. See also
 * Tiny\Xel\Context\RequestContext::validate(), which does the same thing
 * straight off the current request's input.
 *
 * "unique"/"exists" rules need a presence verifier bound to a real
 * connection - usingConnectionResolver() wires that up, and
 * Tiny\Xel\Database\Driver\EloquentDriver already calls it automatically
 * when the "eloquent" db contract is active, so those rules just work
 * without any extra setup either.
 */
final class ValidatorFactory
{
    private static ?Factory $factory = null;

    public static function factory(): Factory
    {
        if (self::$factory === null) {
            $loader = new ArrayLoader();
            $loader->addMessages(
                "en",
                "validation",
                require __DIR__ . "/lang/en/validation.php"
            );

            self::$factory = new Factory(new Translator($loader, "en"));
        }

        return self::$factory;
    }

    /**
     * Enables "unique"/"exists" rules against a real database connection.
     */
    public static function usingConnectionResolver(ConnectionResolverInterface $resolver): void
    {
        self::factory()->setPresenceVerifier(new DatabasePresenceVerifier($resolver));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $customAttributes
     */
    public static function make(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): Validator {
        return self::factory()->make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Validates $data against $rules and returns only the validated
     * fields (Laravel's usual "unknown keys are dropped" behavior).
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $customAttributes
     * @return array<string, mixed>
     * @throws ValidationException when validation fails
     */
    public static function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array {
        $validator = self::make($data, $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
