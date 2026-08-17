<?php

namespace App\Support\Books;

use App\Models\BookBusiness;
use App\Models\User;
use Illuminate\Support\Collection;

class BookContext
{
    public const SESSION_KEY = 'books.business_id';

    public function businesses(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        return $user->bookBusinesses()
            ->where('book_businesses.active', true)
            ->orderBy('book_businesses.name')
            ->get();
    }

    public function current(?User $user = null): ?BookBusiness
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        $businesses = $this->businesses($user);

        if ($businesses->isEmpty()) {
            return null;
        }

        $sessionId = session(self::SESSION_KEY);
        if ($sessionId && $businesses->contains('id', (int) $sessionId)) {
            return $businesses->firstWhere('id', (int) $sessionId);
        }

        if ($user->default_book_business_id && $businesses->contains('id', $user->default_book_business_id)) {
            $business = $businesses->firstWhere('id', $user->default_book_business_id);
            session([self::SESSION_KEY => $business->id]);

            return $business;
        }

        $business = $businesses->first();
        session([self::SESSION_KEY => $business->id]);

        return $business;
    }

    public function select(int $businessId, ?User $user = null): BookBusiness
    {
        $user ??= auth()->user();

        abort_unless($user && $user->canAccessBookBusiness($businessId), 403);

        $business = $user->bookBusinesses()
            ->where('book_businesses.id', $businessId)
            ->where('book_businesses.active', true)
            ->firstOrFail();

        session([self::SESSION_KEY => $business->id]);

        return $business;
    }
}
