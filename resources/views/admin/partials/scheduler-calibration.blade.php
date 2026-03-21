@php
    $modeReports = $schedulerCalibration['mode_reports'] ?? [];
    $spacedSummary = $schedulerCalibration['spaced_summary'] ?? [];
    $userCoverage = $schedulerCalibration['user_coverage'] ?? collect();
    $spacedReport = collect($modeReports)->firstWhere('mode', 'spaced') ?? ['quality' => ['sufficiency' => 'insufficient']];
@endphp

<div class="tab-pane fade {{ $activeTab === 'scheduler-quality' ? 'show active' : '' }}" id="scheduler-quality" role="tabpanel">
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div><i class="fas fa-brain me-2"></i>Scheduler Calibration Coverage</div>
                    <span class="badge bg-{{ ($spacedReport['quality']['sufficiency'] ?? 'insufficient') === 'strong' ? 'success' : ((($spacedReport['quality']['sufficiency'] ?? 'insufficient') === 'moderate') ? 'primary' : 'warning') }}">
                        Spaced: {{ ucfirst($spacedReport['quality']['sufficiency'] ?? 'insufficient') }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">This shows whether the spaced repetition system has enough real evidence to trust its learned calibration, instead of just looking mathematically clean.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase">Spaced Reviews</div>
                                <div class="fs-3 fw-bold">{{ number_format($spacedSummary['total_reviews'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase">Users With Spaced Data</div>
                                <div class="fs-3 fw-bold">{{ number_format($spacedSummary['users_with_reviews'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase">Users Calibrated</div>
                                <div class="fs-3 fw-bold">{{ number_format($spacedSummary['users_with_calibrated_profiles'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase">Confidence Coverage</div>
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    <span class="badge bg-success">Knew {{ $spacedSummary['confidence_counts']['knew'] ?? 0 }}</span>
                                    <span class="badge bg-warning text-dark">Unsure {{ $spacedSummary['confidence_counts']['unsure'] ?? 0 }}</span>
                                    <span class="badge bg-danger">Guessed {{ $spacedSummary['confidence_counts']['guessed'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Mode</th>
                                    <th>Samples</th>
                                    <th>MAE</th>
                                    <th>Brier</th>
                                    <th>Observed</th>
                                    <th>Predicted</th>
                                    <th>Quality</th>
                                    <th>Warnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modeReports as $report)
                                    <tr>
                                        <td class="text-capitalize fw-bold">{{ $report['mode'] }}</td>
                                        <td>{{ $report['sample_size'] }}</td>
                                        <td>{{ $report['mean_abs_error'] !== null ? number_format($report['mean_abs_error'], 4) : 'n/a' }}</td>
                                        <td>{{ $report['brier_score'] !== null ? number_format($report['brier_score'], 4) : 'n/a' }}</td>
                                        <td>{{ $report['observed_recall_rate'] !== null ? number_format($report['observed_recall_rate'] * 100, 1) . '%' : 'n/a' }}</td>
                                        <td>{{ $report['predicted_recall_rate'] !== null ? number_format($report['predicted_recall_rate'] * 100, 1) . '%' : 'n/a' }}</td>
                                        <td>
                                            <span class="badge bg-{{ ($report['quality']['sufficiency'] ?? 'insufficient') === 'strong' ? 'success' : ((($report['quality']['sufficiency'] ?? 'insufficient') === 'moderate') ? 'primary' : ((($report['quality']['sufficiency'] ?? 'insufficient') === 'weak') ? 'warning' : 'danger')) }}">
                                                {{ ucfirst($report['quality']['sufficiency'] ?? 'insufficient') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!empty($report['quality']['warnings']))
                                                <div class="small text-warning-emphasis">
                                                    @foreach($report['quality']['warnings'] as $warning)
                                                        <div>{{ $warning }}</div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-success small">No active warnings</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-3">Users Needing More Evidence</h6>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Reviews</th>
                                    <th>Confidence Mix</th>
                                    <th>Quality</th>
                                    <th>Calibrated Reviews</th>
                                    <th>Reliability</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userCoverage as $entry)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $entry['user']->name }}</div>
                                            <div class="text-muted small">{{ $entry['user']->email }}</div>
                                        </td>
                                        <td>{{ $entry['total_reviews'] }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-success">K {{ $entry['knew_count'] }}</span>
                                                <span class="badge bg-warning text-dark">U {{ $entry['unsure_count'] }}</span>
                                                <span class="badge bg-danger">G {{ $entry['guessed_count'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $entry['sufficiency'] === 'strong' ? 'success' : ($entry['sufficiency'] === 'moderate' ? 'primary' : ($entry['sufficiency'] === 'weak' ? 'warning' : 'danger')) }}">
                                                {{ ucfirst($entry['sufficiency']) }} · {{ number_format($entry['quality_score'], 4) }}
                                            </span>
                                        </td>
                                        <td>{{ $entry['calibration_reviews'] }}</td>
                                        <td>{{ $entry['confidence_reliability'] !== null ? number_format($entry['confidence_reliability'], 4) : 'n/a' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No spaced review evidence recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
