<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * ProposalPolicy — centralised ownership enforcement.
 *
 * AC-13: Every proposal-related action goes through a single policy method,
 *        so new endpoints inherit ownership checks automatically.
 * AC-14: Policy is the sole ownership-check layer (alongside scoped route
 *        binding); no ad-hoc per-controller logic is needed.
 * AC-11: Admins and super-admins bypass all ownership restrictions.
 * AC-16: Every denied attempt is logged to the security audit channel with
 *        user_id, proposal_id, endpoint (action), and UTC timestamp.
 */
class ProposalPolicy
{
    /**
     * Admins and super-admins pass every policy check without restriction.
     * Returning true short-circuits all individual method checks below.
     *
     * AC-11: Privileged roles are not subject to ownership restrictions.
     */
    public function before(User $user, string $ability): ?bool
    {
        $privileged = config('roles.hierarchy.' . $user->role, []);

        if (in_array('admin', $privileged, true)) {
            return true; // admin / super-admin: grant unconditionally
        }

        return null; // defer to individual method for all other roles
    }

    /**
     * Can the user browse the proposal list?
     * (Always yes for their own — scoping to owner is done at query level.)
     *
     * AC-1, AC-2: index() always scopes via the user relationship; policy
     *             merely gates entry.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSales();
    }

    /**
     * Can the user view a specific proposal?
     *
     * AC-3, AC-12: Only the owner may view; 403 returned for mismatched IDs.
     */
    public function view(User $user, Proposal $proposal): bool
    {
        $allowed = $user->id === $proposal->user_id;

        if (! $allowed) {
            $this->logDenial($user, $proposal, 'view');
        }

        return $allowed;
    }

    /**
     * Can the user create a proposal?
     * (Owner is assigned automatically in the controller — AC-9.)
     */
    public function create(User $user): bool
    {
        return $user->isSales();
    }

    /**
     * Can the user clone a proposal? (WB-022 AC-16)
     *
     * Cloning is creating: only sales reps who own the source proposal may
     * clone it.  The `before()` hook already grants admins unconditional access,
     * so this method only runs for non-admin roles.
     */
    public function clone(User $user, Proposal $proposal): bool
    {
        // Must be able to view the proposal AND able to create new ones
        return $user->isSales() && $user->id === $proposal->user_id;
    }

    /**
     * Can the user update (edit) a proposal?
     *
     * AC-4: Only the owner may update; any other sales rep receives 403.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        $allowed = $user->id === $proposal->user_id;

        if (! $allowed) {
            $this->logDenial($user, $proposal, 'update');
        }

        return $allowed;
    }

    /**
     * Can the user delete a proposal?
     *
     * AC-5: Only the owner may delete; any other sales rep receives 403.
     */
    public function delete(User $user, Proposal $proposal): bool
    {
        $allowed = $user->id === $proposal->user_id;

        if (! $allowed) {
            $this->logDenial($user, $proposal, 'delete');
        }

        return $allowed;
    }

    /**
     * Alias used by show(), edit(), update(), and destroy() controllers that
     * were written before individual policy methods were added.
     * Kept for backwards-compatibility; delegates to `update` semantics.
     *
     * @deprecated  Prefer view() / update() / delete() directly.
     */
    public function affect(User $user, Proposal $proposal): bool
    {
        return $this->update($user, $proposal);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /**
     * AC-16: Write a structured warning to the security audit log whenever an
     *        ownership check fails for a sales rep.
     */
    private function logDenial(User $user, Proposal $proposal, string $action): void
    {
        Log::channel('security')->warning('Proposal ownership check failed', [
            'user_id'     => $user->id,
            'user_role'   => $user->role,
            'proposal_id' => $proposal->id,
            'action'      => $action,
            'endpoint'    => request()->path(),
            'method'      => request()->method(),
            'ip'          => request()->ip(),
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);
    }
}
