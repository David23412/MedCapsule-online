@extends('layouts.admin')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-graduation-cap me-2"></i>Course Management</h1>
        <a href="{{ route('add-course.form') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add New Course
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-light d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">All Courses</h6>
            <div class="input-group w-25">
                <input type="text" id="courseSearch" class="form-control form-control-sm" placeholder="Search...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-weight-bold">
                        <tr>
                            <th class="ps-4">Preview</th>
                            <th>Name</th>
                            <th>Academic Year</th>
                            <th>Access Type</th>
                            <th>Topics</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="courseTableBody">
                        @forelse($courses as $course)
                            <tr class="course-row" data-name="{{ strtolower($course->name) }}">
                                <td class="ps-4">
                                    <div class="course-mini-card" style="background-color: {{ $course->color }}; background-image: url('{{ $course->image_url }}');"></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $course->name }}</div>
                                    <div class="small text-muted">{{ Str::limit($course->description, 60) }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $course->academic_year }}</span>
                                </td>
                                <td>
                                    @if($course->access_type === 'free')
                                        <span class="badge bg-success-soft text-success">Free</span>
                                    @elseif($course->access_type === 'preview')
                                        <span class="badge bg-info-soft text-info">Preview</span>
                                    @else
                                        <span class="badge bg-primary-soft text-primary">Paid ({{ $course->price }} EGP)</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-book-open text-muted me-2"></i>
                                        <span>{{ $course->topics_count ?? 0 }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($course->is_published)
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Published</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-pencil-alt me-1"></i>Draft</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Course">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this course?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Course">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No courses found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.course-mini-card {
    width: 60px;
    height: 40px;
    border-radius: 6px;
    background-size: cover;
    background-position: center;
    border: 1px solid rgba(0,0,0,0.1);
}

.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }

.table th { border-top: none; }
.btn-group form { margin-left: 5px; }

.badge {
    padding: 0.5em 0.8em;
    font-weight: 500;
}
</style>

<script>
document.getElementById('courseSearch')?.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.course-row').forEach(row => {
        row.style.display = row.dataset.name.includes(term) ? '' : 'none';
    });
});
</script>
@endsection
