@extends('layouts.app')

@section('title', 'Configuración - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Configuración</h1>
        <p class="text-muted mb-0">Datos de empresa, parámetros financieros y usuarios.</p>
    </section>

    @php $currentPlan = config('plans.'.$company->plan, config('plans.full')); @endphp
    <section class="card content-card mb-4 border-primary">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
                <div class="text-muted small text-uppercase">Licencia activa</div>
                <div class="h5 fw-bold mb-1"><i class="fa-solid fa-id-badge me-2 text-primary"></i>{{ $currentPlan['label'] }}</div>
                <div class="text-muted small mb-0">{{ $currentPlan['description'] }}</div>
            </div>
            <span class="badge {{ $company->plan === 'full' ? 'text-bg-primary' : 'text-bg-secondary' }} fs-6">{{ $currentPlan['label'] }}</span>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Empresa y parámetros</h2>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label">Nombre comercial</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $company->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="plan" class="form-label">Plan / Licencia</label>
                        @can('companies.manage-plan')
                            <select id="plan" name="plan" class="form-select @error('plan') is-invalid @enderror" required>
                                @foreach (config('plans') as $value => $info)
                                    <option value="{{ $value }}" @selected(old('plan', $company->plan) === $value)>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                            @error('plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @else
                            <input type="text" class="form-control" value="{{ $currentPlan['label'] }}" disabled>
                            <div class="form-text"><i class="fa-solid fa-lock me-1"></i>Solo el dueño del sistema puede cambiar la licencia.</div>
                        @endcan
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="rnc" class="form-label">RNC / Identificación</label>
                        <input id="rnc" name="rnc" type="text" value="{{ old('rnc', $company->rnc) }}" class="form-control @error('rnc') is-invalid @enderror">
                        @error('rnc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $company->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $company->email) }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="address" class="form-label">Dirección</label>
                        <input id="address" name="address" type="text" value="{{ old('address', $company->address) }}" class="form-control @error('address') is-invalid @enderror">
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="currency" class="form-label">Moneda</label>
                        <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                            @foreach (config('loan_labels.currencies') as $value => $label)
                                <option value="{{ $value }}" @selected(old('currency', $settings->currency ?? 'RD$') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="default_interest_rate" class="form-label">Interés por defecto</label>
                        <input id="default_interest_rate" name="default_interest_rate" type="number" step="0.0001" min="0" value="{{ old('default_interest_rate', $settings->default_interest_rate ?? 0) }}" class="form-control @error('default_interest_rate') is-invalid @enderror" required>
                        @error('default_interest_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="default_late_fee_type" class="form-label">Tipo de mora</label>
                        <select id="default_late_fee_type" name="default_late_fee_type" class="form-select @error('default_late_fee_type') is-invalid @enderror" required>
                            @foreach (['none' => 'Sin mora', 'fixed' => 'Fija', 'daily_percentage' => 'Porcentaje diario', 'daily_fixed' => 'Monto diario'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('default_late_fee_type', $settings->default_late_fee_type ?? 'none') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('default_late_fee_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="default_late_fee_value" class="form-label">Valor mora</label>
                        <input id="default_late_fee_value" name="default_late_fee_value" type="number" step="0.01" min="0" value="{{ old('default_late_fee_value', $settings->default_late_fee_value ?? 0) }}" class="form-control @error('default_late_fee_value') is-invalid @enderror" required>
                        @error('default_late_fee_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="loan_prefix" class="form-label">Prefijo préstamos</label>
                        <input id="loan_prefix" name="loan_prefix" type="text" value="{{ old('loan_prefix', $settings->loan_prefix ?? 'PRE') }}" class="form-control @error('loan_prefix') is-invalid @enderror" required>
                        @error('loan_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="quote_prefix" class="form-label">Prefijo cotizaciones</label>
                        <input id="quote_prefix" name="quote_prefix" type="text" value="{{ old('quote_prefix', $settings->quote_prefix ?? 'COT') }}" class="form-control @error('quote_prefix') is-invalid @enderror" required>
                        @error('quote_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="receipt_prefix" class="form-label">Prefijo recibos</label>
                        <input id="receipt_prefix" name="receipt_prefix" type="text" value="{{ old('receipt_prefix', $settings->receipt_prefix ?? 'REC') }}" class="form-control @error('receipt_prefix') is-invalid @enderror" required>
                        @error('receipt_prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="route_visit_radius_meters" class="form-label">Radio visita GPS (metros)</label>
                        <input id="route_visit_radius_meters" name="route_visit_radius_meters" type="number" min="20" max="500" step="1" value="{{ old('route_visit_radius_meters', $settings->route_visit_radius_meters ?? 75) }}" class="form-control @error('route_visit_radius_meters') is-invalid @enderror" required>
                        @error('route_visit_radius_meters') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @foreach ([
                        'allow_partial_payments' => 'Permitir pagos parciales',
                        'allow_payment_cancellation' => 'Permitir anulación de pagos',
                        'require_approval_for_loans' => 'Requerir aprobación de préstamos',
                        'exclude_sundays_for_daily_loans' => 'Excluir domingos en préstamos diarios',
                    ] as $field => $label)
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, (bool) ($settings->{$field} ?? false)))>
                                <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar configuración
                    </button>
                </div>
            </form>
        </div>
    </section>

    @php $canManageUsers = auth()->user()->can('users.manage') && \App\Support\MenuAccess::canManageUsers(auth()->user()); @endphp
    @unless ($canManageUsers)
        <section class="card content-card">
            <div class="card-body d-flex align-items-center gap-3 text-muted">
                <i class="fa-solid fa-lock fs-4"></i>
                <div>
                    <div class="fw-semibold">Gestión de usuarios y roles no disponible</div>
                    <div class="small mb-0">Tu plan actual no incluye la administración de usuarios ni roles. Disponible en el <strong>Plan Full Prestamista</strong>.</div>
                </div>
            </div>
        </section>
    @else
    <section class="card content-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h6 text-uppercase text-muted mb-1">Usuarios</h2>
                    <p class="text-muted mb-0">Accesos internos y roles operativos.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-user-shield me-2"></i> Roles
                    </a>
                    <a href="{{ route('users.create') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-user-plus me-2"></i> Nuevo usuario
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('settings.index') }}" class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-6">
                    <label for="search" class="form-label">Buscar usuario</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Nombre, email o teléfono">
                </div>
                <div class="col-12 col-md-4">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activo</option>
                        <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Bloqueado</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último acceso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $managedUser)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $managedUser->name }}</div>
                                    <div class="text-muted small">{{ $managedUser->email }}</div>
                                </td>
                                <td>{{ $managedUser->roles->first()?->name ?: 'Sin rol' }}</td>
                                <td>@include('users.partials.status-badge', ['status' => $managedUser->status])</td>
                                <td>{{ $managedUser->last_login_at?->format('d/m/Y H:i') ?: 'Nunca' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </section>
    @endunless
@endsection
