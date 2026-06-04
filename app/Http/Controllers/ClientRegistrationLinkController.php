<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Clients\StoreClientRegistrationLinkRequest;
use App\Http\Requests\Clients\SubmitClientRegistrationRequest;
use App\Services\Clients\ClientRegistrationLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ClientRegistrationLinkController extends Controller
{
    public function __construct(
        private readonly ClientRegistrationLinkService $linkService,
    ) {
    }

    public function index(Request $request): View
    {
        return view('clients.links', [
            'links' => $this->linkService->latestForCompany((int) $request->user()->company_id),
            'generatedLink' => session('generatedLink'),
            'generatedWhatsappUrl' => session('generatedWhatsappUrl'),
        ]);
    }

    public function store(StoreClientRegistrationLinkRequest $request): RedirectResponse
    {
        $link = $this->linkService->create(
            (int) $request->user()->company_id,
            $request->validated(),
            (int) $request->user()->id,
        );

        $formUrl = route('client-registration.show', $link->token);
        $message = 'Hola'.($link->recipient_name ? ' '.$link->recipient_name : '').', completa tu formulario de registro aquí: '.$formUrl;
        $whatsAppUrl = $link->recipient_phone
            ? 'https://wa.me/'.$this->normalizePhone($link->recipient_phone).'?text='.rawurlencode($message)
            : null;

        return redirect()
            ->route('clients.links.index')
            ->with('status', 'Enlace generado correctamente.')
            ->with('generatedLink', $formUrl)
            ->with('generatedWhatsappUrl', $whatsAppUrl);
    }

    public function showPublic(string $token): View
    {
        try {
            $link = $this->linkService->findAvailableByToken($token);
        } catch (InvalidArgumentException) {
            abort(410, 'Este enlace ya no está disponible.');
        }

        return view('public.client-registration', [
            'link' => $link,
            'googleMapsApiKey' => (string) config('services.google_maps.api_key'),
        ]);
    }

    public function submitPublic(SubmitClientRegistrationRequest $request, string $token): RedirectResponse
    {
        try {
            $link = $this->linkService->findAvailableByToken($token);
            $this->linkService->registerClientFromLink($link, $request->validated());
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'El código ya existe en esta empresa.') {
                return back()->withInput()->withErrors(['code' => $exception->getMessage()]);
            }

            abort(410, 'Este enlace ya no está disponible.');
        }

        return redirect()->route('client-registration.success', $token);
    }

    public function success(string $token): View
    {
        return view('public.client-registration-success');
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
