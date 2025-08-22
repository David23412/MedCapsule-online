@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Video Management</h2>
            
            <!-- Quick Add Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Quick Add Video</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.videos.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <select name="topic_id" class="form-control" required>
                                    <option value="">Select Topic</option>
                                    @foreach($topics as $topic)
                                        <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="youtube_url" class="form-control" placeholder="YouTube URL" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="title" class="form-control" placeholder="Title (optional)">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Add Video</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Videos -->
            <div class="card">
                <div class="card-header">
                    <h5>Existing Videos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Topic</th>
                                    <th>Title</th>
                                    <th>YouTube ID</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($videos as $video)
                                <tr>
                                    <td>{{ $video->topic->name }}</td>
                                    <td>{{ $video->title }}</td>
                                    <td>{{ $video->youtube_video_id }}</td>
                                    <td>
                                        <span class="badge badge-success">Active</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.videos.destroy', $video) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this video?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
