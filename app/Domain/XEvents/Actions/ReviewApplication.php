<?php

namespace App\Domain\XEvents\Actions;

use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Domain\XEvents\Exceptions\XApplicationException;
use App\Models\User;
use App\Models\XApplication;
use Illuminate\Support\Facades\DB;

/**
 * Organizer decisions on X Events applications: approve (access for every X event
 * until revoked), reject, or revoke an approval after reassessment.
 */
final class ReviewApplication
{
    /**
     * @throws XApplicationException
     */
    public function approve(XApplication $application, User $actor, ?string $note = null): void
    {
        $this->decide($application, XApplicationStatus::Approved, $actor, $note);
    }

    /**
     * @throws XApplicationException
     */
    public function reject(XApplication $application, User $actor, ?string $note = null): void
    {
        $this->decide($application, XApplicationStatus::Rejected, $actor, $note);
    }

    /**
     * @throws XApplicationException
     */
    public function revoke(XApplication $application, User $actor, ?string $note = null): void
    {
        if ($application->status !== XApplicationStatus::Approved) {
            throw XApplicationException::notApproved();
        }

        DB::transaction(function () use ($application, $actor, $note) {
            $application->update([
                'status' => XApplicationStatus::Revoked,
                'decision_note' => $note,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);
            $application->hiker->update(['x_approved_at' => null]);
        });
    }

    public function saveNote(XApplication $application, ?string $note): void
    {
        $application->update(['internal_note' => $note]);
    }

    private function decide(XApplication $application, XApplicationStatus $status, User $actor, ?string $note): void
    {
        if ($application->status !== XApplicationStatus::Pending) {
            throw XApplicationException::notPending();
        }

        DB::transaction(function () use ($application, $status, $actor, $note) {
            $application->update([
                'status' => $status,
                'decision_note' => $note,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            if ($status === XApplicationStatus::Approved) {
                $application->hiker->update(['x_approved_at' => now()]);
            }
        });
    }
}
