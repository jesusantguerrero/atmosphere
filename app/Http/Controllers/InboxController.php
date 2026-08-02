<?php

namespace App\Http\Controllers;

class InboxController extends Controller
{
    /**
     * The Inbox is the triage surface for DRAFT transactions — items captured
     * (bank import, quick add) but not yet confirmed. The list is fetched
     * client-side via /api/finance/transactions?filter[status]=draft so the
     * existing confirm/remove machinery (TransactionsList) stays reusable, and
     * the sidebar badge reads the shared `pendingReviewCount` prop.
     */
    public function __invoke()
    {
        return inertia('Inbox/Index');
    }
}
