{{-- Grid de permisos reutilizable. Requiere $permissionGroups y $selectedPermissions. --}}
<section class="row g-3">
    @foreach ($permissionGroups as $group => $permissions)
        <div class="col-12 col-xl-6">
            <article class="card content-card h-100">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between pb-0">
                    <h2 class="h6 fw-bold mb-0">{{ $group }}</h2>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none group-toggle" data-group="{{ $loop->index }}">Marcar todo</button>
                </div>
                <div class="card-body">
                    <div class="vstack gap-2">
                        @foreach ($permissions as $permission)
                            <label class="border rounded-3 p-3 d-flex gap-3 align-items-start">
                                <input class="form-check-input mt-1 perm-check perm-group-{{ $loop->parent->index }}" type="checkbox" name="permissions[]" value="{{ $permission['name'] }}" @checked(in_array($permission['name'], old('permissions', $selectedPermissions), true)) @disabled($disabledAll ?? false)>
                                <span>
                                    <span class="fw-semibold d-block">{{ $permission['label'] }}</span>
                                    <span class="text-muted small">{{ $permission['screen'] }} · {{ $permission['name'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>
    @endforeach
</section>

@push('scripts')
<script>
    document.querySelectorAll('.group-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const boxes = document.querySelectorAll('.perm-group-' + btn.dataset.group);
            const allOn = Array.from(boxes).every((b) => b.checked);
            boxes.forEach((b) => { if (!b.disabled) b.checked = !allOn; });
        });
    });
</script>
@endpush
