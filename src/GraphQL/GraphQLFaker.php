<?php

namespace TJDFT\Laravel\GraphQL;

use Faker\Factory as Faker;
use Faker\Generator;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Language\AST\{FieldDefinitionNode, ListTypeNode, NamedTypeNode, NonNullTypeNode, ObjectTypeDefinitionNode};
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Parser;
use Illuminate\Http\Request;

class GraphQLFaker
{
    protected Generator $faker;

    protected array $types = [];

    protected array $fieldOverrides = [];

    public function __construct()
    {
        $this->faker = Faker::create();
        $this->loadSchema();
        $this->loadOverrides();
    }

    public function __invoke(Request $request)
    {
        // Permite apenas em ambiente local
        $isLocal = in_array($request->host(), ["localhost", "127.0.0.1", "0.0.0.0"]);

        abort_unless($isLocal, 403, 'GraphQL faker permitido apenas em ambiente local.');

        return response()->json(
            $this->handle(
                query: $request->input('query'),
                variables: $request->input('variables', [])
            )
        );
    }

    public function handle(string $query, array $variables = []): array
    {
        try {
            $document = Parser::parse($query);

            foreach ($document->definitions as $definition) {
                if ($definition instanceof OperationDefinitionNode) {
                    return [
                        'data' => $this->fakeSelectionSet(
                            'Query',
                            $definition->selectionSet
                        ),
                    ];
                }
            }

            return ['data' => null];
        } catch (Error $e) {
            return [
                'data' => null,
                'errors' => [
                    FormattedError::createFromException($e),
                ],
            ];
        }
    }

    protected function loadSchema(): void
    {
        $doc = Parser::parse(file_get_contents(base_path(config('tjdft.graphql_faker.schema_path'))));

        foreach ($doc->definitions as $def) {
            if ($def instanceof ObjectTypeDefinitionNode) {
                $this->types[$def->name->value] = $def;
            }
        }
    }

    protected function loadOverrides(): void
    {
        $this->fieldOverrides = require base_path(config('tjdft.graphql_faker.schema_path_overrides'));
    }

    protected function fakeSelectionSet(string $rootType, SelectionSetNode $selectionSet): array
    {
        return $this->fakeObject($rootType, $selectionSet) ?? [];
    }

    protected function fakeObject(string $type, ?SelectionSetNode $selectionSet = null, string $path = ''): ?array
    {
        $definition = $this->types[$type] ?? null;

        if (! $definition) {
            return null;
        }

        $data = [];

        foreach ($selectionSet?->selections ?? [] as $selection) {
            if (! $selection instanceof FieldNode) {
                continue;
            }

            $fieldName = $selection->name->value;

            $fieldDef = collect($definition->fields)->first(fn($f) => $f->name->value === $fieldName);

            if (! $fieldDef) {
                throw new Error("Cannot query field \"{$fieldName}\" on type \"{$type}\".");
            }

            $currentPath = "{$type}.{$fieldName}";

            $data[$fieldName] = $this->fakeField($fieldDef, $currentPath, $selection->selectionSet ?? null);
        }

        return $data;
    }

    protected function fakeField(FieldDefinitionNode $field, string $path, ?SelectionSetNode $selectionSet = null)
    {
        // 🔥 Override tem prioridade total
        if (array_key_exists($path, $this->fieldOverrides)) {
            $value = $this->fieldOverrides[$path];

            return is_callable($value) ? $value() : $value;
        }

        return $this->fakeType($field->type, $path, $selectionSet);
    }

    protected function fakeType($type, string $path, ?SelectionSetNode $selectionSet = null)
    {
        if ($type instanceof NonNullTypeNode) {
            return $this->fakeType($type->type, $path, $selectionSet);
        }

        if ($type instanceof ListTypeNode) {
            $paginator = str($path)->before('.data')->append('.size')->toString();
            $size = rand(1, 2);

            if (array_key_exists($paginator, $this->fieldOverrides)) {
                $size = $this->fieldOverrides[$paginator];
            }

            return collect(range(1, $size))
                ->map(fn() => $this->fakeType($type->type, $path, $selectionSet))
                ->all();
        }

        if ($type instanceof NamedTypeNode) {
            return $this->fakeNamedType($type->name->value, $path, $selectionSet);
        }

        return null;
    }

    protected function fakeNamedType(string $name, string $path, ?SelectionSetNode $selectionSet = null)
    {
        // Não adianta gerar dados aleatórios, pois não há como validar nos testes
        return match ($name) {
            'ID' => (string) $this->faker->uuid(),
            'String' => 'string',
            'Int' => 123,
            'Float' => 123.9,
            'Boolean' => $this->faker->boolean(),
            'Date' => $this->faker->date(),
            default => $this->fakeObject($name, $selectionSet, $path . '.'),
        };
    }
}
