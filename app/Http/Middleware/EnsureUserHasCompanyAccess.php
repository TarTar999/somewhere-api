<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompanyAccess
{
    protected array $roleHierarchy = [
        'member' => 1,
        'manager' => 2,
        'admin' => 3,
    ];

    public function handle(Request $request, Closure $next, ?string $minimumRole = null): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->currentCompany) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No company selected'], 403);
            }
            return redirect()->route('company.select');
        }

        if (!$user->currentCompany->isActive()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Company is suspended'], 403);
            }
            return redirect()->route('company.suspended');
        }

        if (!$user->currentCompany->hasActiveSubscription()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Subscription expired'], 403);
            }
            return redirect()->route('company.subscription.expired');
        }

        // Check minimum role if specified
        if ($minimumRole) {
            $userRole = $user->getCompanyRole();
            $requiredLevel = $this->roleHierarchy[$minimumRole] ?? 0;
            $userLevel = $this->roleHierarchy[$userRole] ?? 0;

            if ($userLevel < $requiredLevel) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Insufficient permissions'], 403);
                }
                abort(403, 'Insufficient permissions');
            }
        }

        return $next($request);
    }
}
