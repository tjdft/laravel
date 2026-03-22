<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\User;

/**
 * @template TModel of User
 *
 * @extends Factory<TModel>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->unique()->uuid,
            'login' => $this->faker->unique()->userName,
            'matricula' => (string) $this->faker->unique()->randomNumber(4),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'nome' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'foto' => $this->faker->imageUrl,
            'rh_tipo' => $this->faker->randomElement(['MAGISTRADO', 'SERVIDOR']),
            'rh_status' => $this->faker->randomElement(['ATIVO', 'INATIVO']),
            'competencia' => null,
            'localizacao' => [
                'id' => 1,
                'codigo' => '10002000300',
                'sigla' => 'LOC',
                'nome' => 'localizacao'
            ]
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
