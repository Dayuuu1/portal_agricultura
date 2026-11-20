<x-layouts.app title="Convocatorias" description="Convocatorias CAS y procesos de selección de personal: bases, cronogramas y resultados.">
    <x-page-header title="Convocatorias" subtitle="Procesos de selección de personal (CAS) con sus bases, cronogramas y resultados de cada etapa." :breadcrumbs="['Convocatorias' => null]" />

    <div class="container-site mt-10">
        <form method="GET" action="{{ route('job-postings.index') }}" class="card mb-8 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-input">
                    <option value="">Todos los estados</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status)>{{ $status->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label for="anio" class="form-label">Año</label>
                <select id="anio" name="anio" class="form-input">
                    <option value="">Todos los años</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected($filters['year'] === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Filtrar</button>
        </form>

        @if ($jobPostings->isNotEmpty())
            <div class="space-y-3">
                @foreach ($jobPostings as $jobPosting)
                    <x-job-posting-item :job-posting="$jobPosting" />
                @endforeach
            </div>
            <div class="mt-8">{{ $jobPostings->links() }}</div>
        @else
            <x-empty-state icon="heroicon-o-briefcase" title="No hay convocatorias con estos filtros" />
        @endif
    </div>
</x-layouts.app>
