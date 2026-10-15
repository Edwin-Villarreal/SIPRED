@extends('layouts.app')

@section('title', $title)
@section('section', $title)

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ mb_strtoupper($section) }} · SIPRED</span>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
        </div>
        <span class="phase-badge"><span class="phase-indicator" aria-hidden="true"></span> Datos del sistema</span>
    </section>

    <section class="module-stat-grid" aria-label="Resumen de {{ $title }}">
        @foreach ($stats as $stat)
            <article class="module-stat-card">
                <span>{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
            </article>
        @endforeach
    </section>

    <section class="panel measurement-page-panel">
        <div class="panel-heading">
            <div>
                <span class="panel-kicker">{{ mb_strtoupper($section) }}</span>
                <h2>{{ $title }}</h2>
            </div>
            @if ($searchable)
                <form class="module-search" method="GET" action="{{ url()->current() }}">
                    <label class="visually-hidden" for="module-search">Buscar en {{ $title }}</label>
                    <input id="module-search" type="search" name="q" value="{{ $search }}" placeholder="Buscar registros">
                    <button class="btn-sipred" type="submit">Buscar</button>
                </form>
            @endif
        </div>

        @if ($rows->isEmpty())
            <div class="measurement-empty" role="status">{{ $emptyMessage }}</div>
        @else
            <div class="measurement-table-wrap">
                <table class="measurement-table module-table">
                    <thead>
                        <tr>@foreach ($columns as $column)<th scope="col">{{ $column['label'] }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($columns as $column)
                                    @php($cell = $row[$column['key']] ?? '—')
                                    <td>
                                        @if (is_array($cell))
                                            @if (!empty($cell['href']))
                                                <a href="{{ $cell['href'] }}">{{ $cell['value'] ?? '—' }}</a>
                                            @else
                                                {{ $cell['value'] ?? '—' }}
                                            @endif
                                            @if (!empty($cell['hint']))<small>{{ $cell['hint'] }}</small>@endif
                                        @else
                                            {{ $cell }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($pagination)
                <nav class="measurement-pagination" aria-label="Paginación de {{ $title }}">
                    <span>Mostrando {{ $pagination->firstItem() }}–{{ $pagination->lastItem() }} de {{ $pagination->total() }}</span>
                    <div>
                        @if ($pagination->previousPageUrl())<a href="{{ $pagination->previousPageUrl() }}">← Anterior</a>@endif
                        <span> · Página {{ $pagination->currentPage() }} de {{ $pagination->lastPage() }} · </span>
                        @if ($pagination->nextPageUrl())<a href="{{ $pagination->nextPageUrl() }}">Siguiente →</a>@endif
                    </div>
                </nav>
            @endif
        @endif
    </section>
@endsection