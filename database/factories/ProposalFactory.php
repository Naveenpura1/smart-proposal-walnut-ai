<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory()->sales(),
            'proposal_title'    => fake()->sentence(4),
            'client_name'       => fake()->name(),
            'client_company'    => fake()->company(),
            'client_email'      => fake()->unique()->safeEmail(),
            'industry'          => fake()->randomElement(['SaaS', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'pain_points'       => fake()->paragraph(),
            'deal_size'         => fake()->randomFloat(2, 5000, 500000),
            'generated_content' => fake()->paragraphs(3, true),
            'status'            => fake()->randomElement(Proposal::STATUSES),
        ];
    }

    /** Owned by a specific user */
    public function ownedBy(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /** Draft status */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'Draft']);
    }

    /** Sent status */
    public function sent(): static
    {
        return $this->state(fn () => ['status' => 'Sent']);
    }

    /** Accepted status */
    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'Accepted']);
    }
}
