<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $parameter = $request->route('stock');

        if ($parameter === null) {
            return $next($request);
        }

        $stock = $parameter instanceof Stock ? $parameter : Stock::query()->find($parameter);

        if (! $stock) {
            abort(404);
        }

        if ($stock->user_id !== $request->user()?->id) {
            abort(403);
        }

        return $next($request);
    }
}
