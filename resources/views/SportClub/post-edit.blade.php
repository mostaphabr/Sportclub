@extends('SportClub.layouts.parts')

@section('content')
    <div class="container my-5" style="max-width: 760px">
        <a href="{{ route('posts.show', $post->id) }}" class="small">&larr; Back to the post</a>
        <h1 class="h2 mt-2 mb-4">Edit post</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('posts.update', $post) }}" method="POST" enctype="multipart/form-data" class="card shadow-sm">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $post->title) }}" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Text</label>
                    <textarea class="form-control" id="description" name="description" rows="8" required>{{ old('description', $post->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Image</label>
                    <img src="{{ $post->imageUrl() }}" alt="" class="rounded mb-2" style="max-width: 220px; max-height: 140px; object-fit: cover">
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <div class="form-text">Leave empty to keep the current image.</div>
                </div>

                <button type="submit" class="btn btn-info">Save changes</button>
                <a href="{{ route('posts.show', $post->id) }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('title', 'edit post')
