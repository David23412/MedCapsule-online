@extends('layouts.admin')

@section('styles')
<style>
    .question-pdf-page .page-title {
        font-weight: 800;
        color: #1f2937;
    }

    .question-pdf-page .builder-card {
        border-radius: 8px;
        overflow: hidden;
    }

    .question-pdf-page .builder-card .card-header {
        background: #1e5f9f;
        color: #fff;
        border-bottom: 0;
    }

    .question-pdf-page .selection-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        min-height: 220px;
        max-height: 420px;
        overflow: auto;
    }

    .question-pdf-page .selection-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .question-pdf-page .selection-row:last-child {
        border-bottom: 0;
    }

    .question-pdf-page .selection-row:hover {
        background: #f8fbff;
    }

    .question-pdf-page .selection-row input {
        margin-top: 4px;
    }

    .question-pdf-page .selection-title {
        font-weight: 700;
        color: #111827;
    }

    .question-pdf-page .selection-meta {
        color: #6b7280;
        font-size: 0.86rem;
    }

    .question-pdf-page .empty-state {
        display: flex;
        min-height: 180px;
        align-items: center;
        justify-content: center;
        color: #64748b;
        text-align: center;
        padding: 24px;
    }

    .question-pdf-page .toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: space-between;
        align-items: center;
    }

    .question-pdf-page .summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        font-weight: 700;
        padding: 7px 12px;
        font-size: 0.86rem;
    }
</style>
@endsection

@section('content')
<div class="question-pdf-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 page-title mb-1">Question PDF Builder</h1>
            <p class="text-muted mb-0">Export full question packs by course, selected topics, and selected sources.</p>
        </div>
        <span class="summary-pill" id="questionPdfSummary">
            <i class="fas fa-layer-group"></i>
            Select a course to start
        </span>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.question-pdf.export') }}" id="questionPdfForm" class="card builder-card">
        @csrf
        <div class="card-header">
            <h2 class="h5 mb-0"><i class="fas fa-file-pdf me-2"></i>Build PDF</h2>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12">
                    <label for="course_id" class="form-label fw-bold">Course</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select course...</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}">
                                {{ $course->name }} ({{ $course->topics_count }} topics, {{ $course->questions_count }} questions)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-6">
                    <div class="toolbar mb-2">
                        <label class="form-label fw-bold mb-0">Topics</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" id="selectAllTopics" disabled>Select all</button>
                            <button type="button" class="btn btn-outline-secondary" id="clearTopics" disabled>Clear</button>
                        </div>
                    </div>
                    <div class="selection-panel" id="topicsPanel">
                        <div class="empty-state">Select a course to load topics.</div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="toolbar mb-2">
                        <label class="form-label fw-bold mb-0">Sources</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" id="selectAllSources" disabled>Select all</button>
                            <button type="button" class="btn btn-outline-secondary" id="clearSources" disabled>Clear</button>
                        </div>
                    </div>
                    <div class="selection-panel" id="sourcesPanel">
                        <div class="empty-state">Select one or more topics to load sources.</div>
                    </div>
                </div>

                <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                    <div class="form-check">
                        <input type="hidden" name="include_answer_key" value="0">
                        <input class="form-check-input" type="checkbox" value="1" id="include_answer_key" name="include_answer_key">
                        <label class="form-check-label fw-semibold" for="include_answer_key">
                            Include answer key at the end
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" id="exportPdfButton" disabled>
                        <i class="fas fa-download me-2"></i>Download PDF
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (() => {
        const courseSelect = document.getElementById('course_id');
        const topicsPanel = document.getElementById('topicsPanel');
        const sourcesPanel = document.getElementById('sourcesPanel');
        const summary = document.getElementById('questionPdfSummary');
        const exportButton = document.getElementById('exportPdfButton');
        const selectAllTopics = document.getElementById('selectAllTopics');
        const clearTopics = document.getElementById('clearTopics');
        const selectAllSources = document.getElementById('selectAllSources');
        const clearSources = document.getElementById('clearSources');
        const topicsUrlTemplate = @json(route('admin.question-pdf.topics', ['course' => '__COURSE__']));
        const sourcesUrl = @json(route('admin.question-pdf.sources'));

        const setEmpty = (element, message) => {
            element.innerHTML = `<div class="empty-state">${message}</div>`;
        };

        const checkedValues = (name) => Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map((input) => input.value);

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));

        const updateSummary = () => {
            const topicCount = checkedValues('topic_ids[]').length;
            const sourceCount = checkedValues('sources[]').length;
            exportButton.disabled = !courseSelect.value || topicCount === 0 || sourceCount === 0;
            summary.innerHTML = `<i class="fas fa-layer-group"></i>${topicCount} topic${topicCount === 1 ? '' : 's'} · ${sourceCount} source${sourceCount === 1 ? '' : 's'}`;
        };

        const loadSources = async () => {
            const topicIds = checkedValues('topic_ids[]');
            selectAllSources.disabled = true;
            clearSources.disabled = true;
            exportButton.disabled = true;

            if (!courseSelect.value || topicIds.length === 0) {
                setEmpty(sourcesPanel, 'Select one or more topics to load sources.');
                updateSummary();
                return;
            }

            setEmpty(sourcesPanel, 'Loading sources...');
            const params = new URLSearchParams({ course_id: courseSelect.value });
            topicIds.forEach((id) => params.append('topic_ids[]', id));

            const response = await fetch(`${sourcesUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!response.ok || !data.success || data.sources.length === 0) {
                setEmpty(sourcesPanel, 'No sources found for the selected topics.');
                updateSummary();
                return;
            }

            sourcesPanel.innerHTML = data.sources.map((source, index) => `
                <label class="selection-row" for="source_${index}">
                    <input class="form-check-input question-pdf-source" type="checkbox" id="source_${index}" name="sources[]" value="${escapeHtml(source.name)}" checked>
                    <span>
                        <span class="selection-title">${escapeHtml(source.name)}</span>
                        <span class="selection-meta d-block">${source.questions_count} questions</span>
                    </span>
                </label>
            `).join('');

            selectAllSources.disabled = false;
            clearSources.disabled = false;
            sourcesPanel.querySelectorAll('input').forEach((input) => input.addEventListener('change', updateSummary));
            updateSummary();
        };

        courseSelect.addEventListener('change', async () => {
            selectAllTopics.disabled = true;
            clearTopics.disabled = true;
            selectAllSources.disabled = true;
            clearSources.disabled = true;
            setEmpty(sourcesPanel, 'Select one or more topics to load sources.');

            if (!courseSelect.value) {
                setEmpty(topicsPanel, 'Select a course to load topics.');
                updateSummary();
                return;
            }

            setEmpty(topicsPanel, 'Loading topics...');
            const response = await fetch(topicsUrlTemplate.replace('__COURSE__', courseSelect.value), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!response.ok || !data.success || data.topics.length === 0) {
                setEmpty(topicsPanel, 'No topics found for this course.');
                updateSummary();
                return;
            }

            topicsPanel.innerHTML = data.topics.map((topic) => `
                <label class="selection-row" for="topic_${topic.id}">
                    <input class="form-check-input question-pdf-topic" type="checkbox" id="topic_${topic.id}" name="topic_ids[]" value="${topic.id}">
                    <span>
                        <span class="selection-title">${escapeHtml(topic.name)}</span>
                        <span class="selection-meta d-block">${escapeHtml(topic.subject || 'No subject')} · ${topic.questions_count} questions</span>
                    </span>
                </label>
            `).join('');

            selectAllTopics.disabled = false;
            clearTopics.disabled = false;
            topicsPanel.querySelectorAll('input').forEach((input) => input.addEventListener('change', loadSources));
            updateSummary();
        });

        selectAllTopics.addEventListener('click', () => {
            topicsPanel.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = true);
            loadSources();
        });

        clearTopics.addEventListener('click', () => {
            topicsPanel.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = false);
            loadSources();
        });

        selectAllSources.addEventListener('click', () => {
            sourcesPanel.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = true);
            updateSummary();
        });

        clearSources.addEventListener('click', () => {
            sourcesPanel.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = false);
            updateSummary();
        });
    })();
</script>
@endsection
