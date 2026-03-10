<?php

namespace TJDFT\Laravel\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class NoCoreModelPersistenceFromModulesRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection()?->getName();

        if ($class === null || ! str_starts_with($class, 'Modulos\\')) {
            return [];
        }

        $forbiddenMethods = ['save', 'update', 'delete'];

        // $model->save()
        if ($node instanceof MethodCall) {
            if (! $node->name instanceof Node\Identifier) {
                return [];
            }

            $method = $node->name->toString();

            if (! in_array($method, $forbiddenMethods, true)) {
                return [];
            }

            $type = $scope->getType($node->var);

            foreach ($type->getReferencedClasses() as $calledClass) {
                if (str_starts_with($calledClass, 'App\\Models\\')) {
                    return [
                        RuleErrorBuilder::message(
                            "Modules cannot call {$method}() on core models."
                        )
                            ->identifier('architecture.module.persistence')
                            ->build(),
                    ];
                }
            }
        }

        // Model::create()
        if ($node instanceof StaticCall) {
            if (! $node->name instanceof Node\Identifier) {
                return [];
            }

            if ($node->name->toString() !== 'create') {
                return [];
            }

            $calledClass = $scope->resolveName($node->class);

            if ($calledClass && str_starts_with($calledClass, 'App\\Models\\')) {
                return [
                    RuleErrorBuilder::message(
                        'Modules cannot call create() on core models.'
                    )
                        ->identifier('architecture.module.persistence')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
