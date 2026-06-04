<?php

declare(strict_types=1);

namespace App\Support\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Filtros normalizados para los reportes. Inmutable: se construye una vez por
 * request y se pasa al ReportService / ReportScope. Resuelve el rango de fechas
 * a partir de presets (día, semana actual/pasada/antepasada, mes, año) o de un
 * rango personalizado, y conserva las dimensiones (zona/ruta/cobrador/cliente).
 */
class ReportFilters
{
    /** Presets de período soportados. */
    public const PRESETS = [
        'today' => 'Hoy',
        'this_week' => 'Semana actual',
        'last_week' => 'Semana pasada',
        'week_before_last' => 'Semana antepasada',
        'this_month' => 'Mes actual',
        'this_year' => 'Año actual',
        'custom' => 'Rango personalizado',
    ];

    public function __construct(
        public readonly Carbon $dateFrom,
        public readonly Carbon $dateTo,
        public readonly string $preset = 'custom',
        public readonly ?int $zoneId = null,
        public readonly ?int $routeId = null,
        public readonly ?int $collectorId = null,
        public readonly ?int $clientId = null,
        public readonly ?string $loanStatus = null,
        public readonly ?string $clientStatus = null,
        public readonly ?int $year = null,
        public readonly ?string $search = null,
    ) {
    }

    /**
     * Construye los filtros desde la request. Lanza ValidationException si el
     * rango es inválido (Laravel lo convierte en redirect con errores en web).
     */
    public static function fromRequest(Request $request): self
    {
        $validated = Validator::make($request->all(), [
            'preset' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'zone_id' => ['nullable', 'integer'],
            'route_id' => ['nullable', 'integer'],
            'collector_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'loan_status' => ['nullable', 'string'],
            'client_status' => ['nullable', 'string'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'search' => ['nullable', 'string', 'max:120'],
        ], [
            'date_to.after_or_equal' => 'La fecha "hasta" no puede ser menor que la fecha "desde".',
        ])->validate();

        $preset = $validated['preset'] ?? null;
        $year = isset($validated['year']) ? (int) $validated['year'] : null;

        // Sin preset explícito: si llegan fechas es "custom"; si no, mes actual.
        if (! $preset) {
            $preset = isset($validated['date_from']) || isset($validated['date_to']) ? 'custom' : 'this_month';
        }

        [$from, $to] = self::resolveRange($preset, $validated, $year);

        return new self(
            dateFrom: $from,
            dateTo: $to,
            preset: $preset,
            zoneId: self::intOrNull($validated['zone_id'] ?? null),
            routeId: self::intOrNull($validated['route_id'] ?? null),
            collectorId: self::intOrNull($validated['collector_id'] ?? null),
            clientId: self::intOrNull($validated['client_id'] ?? null),
            loanStatus: self::stringOrNull($validated['loan_status'] ?? null),
            clientStatus: self::stringOrNull($validated['client_status'] ?? null),
            year: $year,
            search: self::stringOrNull($validated['search'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function resolveRange(string $preset, array $validated, ?int $year): array
    {
        return match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'week_before_last' => [now()->subWeeks(2)->startOfWeek(), now()->subWeeks(2)->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_year' => self::yearRange($year ?? (int) now()->year),
            default => [
                Carbon::parse($validated['date_from'] ?? now()->startOfMonth()->toDateString())->startOfDay(),
                Carbon::parse($validated['date_to'] ?? now()->toDateString())->endOfDay(),
            ],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function yearRange(int $year): array
    {
        $base = Carbon::create($year, 1, 1)->startOfYear();

        return [$base->copy(), $base->copy()->endOfYear()];
    }

    /** Etiqueta legible del período aplicado (para encabezados/PDF). */
    public function periodLabel(): string
    {
        if ($this->preset === 'this_year' || ($this->preset === 'custom' && $this->year)) {
            return 'Año '.($this->year ?? $this->dateFrom->year);
        }

        if ($this->dateFrom->isSameDay($this->dateTo)) {
            return $this->dateFrom->format('d/m/Y');
        }

        return $this->dateFrom->format('d/m/Y').' — '.$this->dateTo->format('d/m/Y');
    }

    /**
     * Representación apta para repoblar el formulario y conservar los filtros
     * en enlaces de exportación/paginación.
     *
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return array_filter([
            'preset' => $this->preset,
            'date_from' => $this->dateFrom->toDateString(),
            'date_to' => $this->dateTo->toDateString(),
            'zone_id' => $this->zoneId,
            'route_id' => $this->routeId,
            'collector_id' => $this->collectorId,
            'client_id' => $this->clientId,
            'loan_status' => $this->loanStatus,
            'client_status' => $this->clientStatus,
            'year' => $this->year,
            'search' => $this->search,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private static function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return ($value === null || $value === '') ? null : (string) $value;
    }
}
