<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Domain\Offerwalls\Models\PostbackLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyProviderIpMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providerSlug = (string) $request->route('provider');
        $provider = OfferwallProvider::where('slug', $providerSlug)
            ->where('is_active', true)
            ->first();

        if ($provider === null) {
            return response()->json([
                'status' => 'error',
                'message' => "Unknown or inactive offerwall provider: {$providerSlug}",
            ], Response::HTTP_NOT_FOUND);
        }

        $clientIp = $this->getClientIp($request);
        $allowedIps = $provider->allowed_ips ?? [];

        // If allowlist is empty or IP matches, proceed
        if (!empty($allowedIps) && !$this->isIpAllowed($clientIp, $allowedIps)) {
            // Log raw rejection audit
            PostbackLog::create([
                'provider_slug' => $providerSlug,
                'external_transaction_id' => (string) ($request->input('transaction_id') ?? $request->input('tx') ?? $request->input('trans_id')),
                'ip_address' => $clientIp,
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
                'status' => 'rejected_ip',
                'message' => "IP [{$clientIp}] not present in provider allowlist.",
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: Origin IP not authorized by provider allowlist.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Attach provider model to request attributes
        $request->attributes->set('offerwall_provider', $provider);

        return $next($request);
    }

    /**
     * Get real client IP considering Cloudflare CF-Connecting-IP.
     */
    private function getClientIp(Request $request): string
    {
        return (string) ($request->header('CF-Connecting-IP') ?? $request->ip() ?? '127.0.0.1');
    }

    /**
     * Check if IP is in list of allowed IPs / CIDR blocks.
     *
     * @param array<string> $allowedList
     */
    private function isIpAllowed(string $ip, array $allowedList): bool
    {
        foreach ($allowedList as $allowed) {
            $allowed = trim($allowed);
            if ($allowed === '*' || $allowed === $ip) {
                return true;
            }

            // Localhost / Loopback equivalents
            if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true) && in_array($allowed, ['127.0.0.1', '::1', 'localhost'], true)) {
                return true;
            }

            // CIDR check
            if (str_contains($allowed, '/') && $this->ipInCidr($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check IPv4 in CIDR notation.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        [$subnet, $mask] = explode('/', $cidr);
        if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $maskBits = (int) $mask;
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - $maskBits);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
