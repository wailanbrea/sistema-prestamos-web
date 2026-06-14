<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\SignContractRequest;
use App\Models\Contract;
use App\Services\Contracts\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractSigningController extends Controller
{
    public function __construct(private readonly ContractService $contractService)
    {
    }

    public function show(Request $request, string $uuid): View|RedirectResponse
    {
        $contract = $this->findOrFail($uuid);

        if ($contract->isSigned()) {
            return redirect()->route('contracts.public.success', $uuid);
        }

        if ($contract->isFinalized()) {
            abort(410, 'Este contrato ya no está disponible para firma.');
        }

        $this->contractService->markViewed($contract);
        $contract->load(['client', 'loan']);

        return view('contracts.public.sign', [
            'contract' => $contract,
            'loan' => $contract->loan,
            'company' => $contract->load('company')->company,
            'downloadUrl' => $this->signedDownloadUrl($contract),
        ]);
    }

    public function sign(SignContractRequest $request, string $uuid): RedirectResponse
    {
        $contract = $this->findOrFail($uuid);

        try {
            $this->contractService->sign($contract, [
                'signer_name' => $request->string('signer_name')->value(),
                'signature_image' => $request->string('signature')->value(),
                'accepted_terms' => $request->boolean('accepted_terms'),
                'accepted_legal' => $request->boolean('accepted_legal'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $this->deviceType($request->userAgent()),
                'browser' => $this->browser($request->userAgent()),
                'platform' => $this->platform($request->userAgent()),
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['signature' => $e->getMessage()]);
        }

        return redirect()->route('contracts.public.success', $uuid);
    }

    public function success(string $uuid): View
    {
        $contract = $this->findOrFail($uuid);

        return view('contracts.public.success', [
            'contract' => $contract,
            'downloadUrl' => $this->signedDownloadUrl($contract),
        ]);
    }

    public function download(string $uuid): StreamedResponse
    {
        $contract = $this->findOrFail($uuid)->load('document');
        abort_if($contract->document === null, 404);
        abort_unless(Storage::disk('local')->exists($contract->document->file_path), 404);

        return Storage::disk('local')->download($contract->document->file_path, $contract->contract_number.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function verify(string $uuid): View
    {
        $contract = $this->findOrFail($uuid)->load(['loan.client', 'loan.installments', 'loan.company.settings', 'signatures']);

        // Si el préstamo ya no está disponible, mostramos el estado del contrato
        // sin recalcular el hash (no es posible) en vez de fallar con un 500.
        $expected = null;
        $matches = false;
        if ($contract->loan !== null) {
            try {
                $expected = $this->contractService->contentHash($contract->loan, $contract);
                $matches = hash_equals((string) $contract->hash_sha256, $expected);
            } catch (\Throwable $e) {
                $expected = null;
                $matches = false;
            }
        }

        return view('contracts.public.verify', [
            'contract' => $contract,
            'hashMatches' => $matches,
            'expectedHash' => $expected,
        ]);
    }

    private function findOrFail(string $uuid): Contract
    {
        return Contract::query()->where('uuid', $uuid)->firstOrFail();
    }

    private function signedDownloadUrl(Contract $contract): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'contracts.public.download',
            now()->addDays(15),
            ['uuid' => $contract->uuid],
        );
    }

    private function deviceType(?string $ua): string
    {
        $ua = (string) $ua;
        if (preg_match('/iPad|Tablet/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Mobi|Android|iPhone/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            (bool) preg_match('/Edg/i', $ua) => 'Edge',
            (bool) preg_match('/OPR|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Chrome/i', $ua) => 'Chrome',
            (bool) preg_match('/Firefox/i', $ua) => 'Firefox',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            default => 'Desconocido',
        };
    }

    private function platform(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/iPhone|iPad|iOS/i', $ua) => 'iOS',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/Mac OS/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => 'Desconocido',
        };
    }
}
