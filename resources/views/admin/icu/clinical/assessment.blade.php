@extends('layouts.adminlte')

@section('page_title', 'Critical Care Assessment')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Prior Assessments') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($episode->assessments as $assessment)
                        <tr>
                            <td>{{ $assessment->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-info">{{ $assessment->status }}</span></td>
                            <td>
                                @if($assessment->status === 'draft')
                                    <form method="POST" action="{{ route('admin.icu.assessments.finalize', $assessment) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-xs btn-success">{{ __('Finalize') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">{{ __('No assessments recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Assessment') }}</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.icu.episodes.assessment.store', $episode) }}">
                @csrf
                @foreach(['general', 'respiratory', 'cardiovascular', 'neurological', 'renal', 'gastrointestinal', 'skin', 'infection'] as $section)
                    <div class="form-group">
                        <label>{{ ucwords($section) }}</label>
                        <textarea name="sections[{{ $section }}]" class="form-control"></textarea>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-primary">{{ __('Save Assessment') }}</button>
            </form>
        </div>
    </div>
@stop
