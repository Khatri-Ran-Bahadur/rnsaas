<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaDirectory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Display the Media Library view.
     */
    public function page(): Response
    {
        return Inertia::render('Media/Index');
    }

    /**
     * Fetch media files and directory tree for the library.
     */
    public function index(Request $request): JsonResponse
    {
        $directoryId = $request->input('directory_id');
        $search = $request->input('search');
        $type = $request->input('type');
        $tenantId = $request->input('tenant_id');

        $mediaQuery = Media::query();

        // Tenant Scoping
        if ($tenantId !== null) {
            $mediaQuery->where('tenant_id', $tenantId);
        } else {
            $mediaQuery->whereNull('tenant_id');
        }

        // Directory filter
        if ($directoryId !== null && $directoryId !== '') {
            $mediaQuery->where('directory_id', $directoryId);
        }

        // Search query
        if ($search) {
            $mediaQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $media = $mediaQuery->latest()
            ->get()
            ->filter(function (Media $item) use ($type) {
                if ($type && $type !== 'all') {
                    return $item->file_type === $type;
                }

                return true;
            })
            ->values()
            ->map(function (Media $item) {
                return [
                    'id' => $item->id,
                    'tenant_id' => $item->tenant_id,
                    'name' => $item->name,
                    'file_name' => $item->file_name,
                    'url' => $item->url,
                    'thumb_url' => $item->thumb_url,
                    'size' => $item->size,
                    'human_size' => $item->human_size,
                    'mime_type' => $item->mime_type,
                    'file_type' => $item->file_type,
                    'directory_id' => $item->directory_id,
                    'created_by' => $item->created_by,
                    'created_at' => $item->created_at?->toISOString() ?? now()->toISOString(),
                ];
            });

        // Directories tree
        $dirQuery = MediaDirectory::query();
        if ($tenantId !== null) {
            $dirQuery->where('tenant_id', $tenantId);
        } else {
            $dirQuery->whereNull('tenant_id');
        }

        $directories = $dirQuery->whereNull('parent_id')
            ->withCount('media')
            ->with(['children' => function ($q): void {
                $q->withCount('media');
            }])
            ->get()
            ->map(function (MediaDirectory $dir) {
                return [
                    'id' => $dir->id,
                    'tenant_id' => $dir->tenant_id,
                    'name' => $dir->name,
                    'slug' => $dir->slug,
                    'parent_id' => $dir->parent_id,
                    'media_count' => $dir->media_count,
                    'children' => $dir->children->map(fn (MediaDirectory $c) => [
                        'id' => $c->id,
                        'tenant_id' => $c->tenant_id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'parent_id' => $c->parent_id,
                        'media_count' => $c->media_count,
                    ]),
                ];
            });

        return response()->json([
            'media' => $media,
            'directories' => $directories,
        ]);
    }

    /**
     * Handle batch file upload.
     */
    public function batchStore(Request $request): JsonResponse
    {
        $maxSize = (int) config('media.max_upload_size', 51200);

        $request->validate([
            'files' => 'required|array',
            'files.*' => "required|file|max:{$maxSize}",
            'directory_id' => 'nullable|exists:media_directories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $uploaded = [];
        $directoryId = $request->input('directory_id');
        $tenantId = $request->input('tenant_id');
        $disk = config('media.disk', 'public');

        foreach ($request->file('files') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::slug($originalName).'-'.time().'-'.Str::random(6).'.'.$extension;

            // Store file to disk under 'media/' directory
            $file->storeAs('media', $fileName, $disk);

            $media = Media::create([
                'tenant_id' => $tenantId,
                'name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
                'disk' => $disk,
                'size' => $file->getSize(),
                'directory_id' => $directoryId,
                'created_by' => Auth::id(),
            ]);

            $uploaded[] = [
                'id' => $media->id,
                'tenant_id' => $media->tenant_id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'url' => $media->url,
                'thumb_url' => $media->thumb_url,
                'size' => $media->size,
                'human_size' => $media->human_size,
                'mime_type' => $media->mime_type,
                'file_type' => $media->file_type,
                'directory_id' => $media->directory_id,
                'created_at' => $media->created_at?->toISOString() ?? now()->toISOString(),
            ];
        }

        return response()->json([
            'message' => count($uploaded).' file(s) uploaded successfully.',
            'media' => $uploaded,
        ]);
    }

    /**
     * Download a media file.
     */
    public function download(int $id): StreamedResponse|JsonResponse
    {
        $media = Media::findOrFail($id);
        $disk = $media->disk ?? 'public';
        $path = 'media/'.$media->file_name;

        if (! Storage::disk($disk)->exists($path)) {
            return response()->json(['message' => 'File not found on storage.'], 404);
        }

        return Storage::disk($disk)->download($path, $media->name);
    }

    /**
     * Delete a single media file.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = Media::findOrFail($id);
        $disk = $media->disk ?? 'public';
        $path = 'media/'.$media->file_name;

        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        } catch (\Throwable $e) {
            // Ignore storage deletion errors
        }

        $media->delete();

        return response()->json(['message' => 'Media file deleted successfully.']);
    }

    /**
     * Bulk delete media files.
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:media,id',
        ]);

        $ids = $request->input('ids');
        $mediaItems = Media::whereIn('id', $ids)->get();

        foreach ($mediaItems as $media) {
            $disk = $media->disk ?? 'public';
            $path = 'media/'.$media->file_name;
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable $e) {
                // Ignore storage exceptions
            }
            $media->delete();
        }

        return response()->json([
            'message' => count($mediaItems).' media file(s) deleted successfully.',
        ]);
    }

    /**
     * Create a directory/folder.
     */
    public function createDirectory(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_directories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $slug = Str::slug($request->input('name').'-'.time());

        $directory = MediaDirectory::create([
            'tenant_id' => $request->input('tenant_id'),
            'name' => $request->input('name'),
            'slug' => $slug,
            'parent_id' => $request->input('parent_id'),
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Folder created successfully.',
            'directory' => [
                'id' => $directory->id,
                'tenant_id' => $directory->tenant_id,
                'name' => $directory->name,
                'slug' => $directory->slug,
                'parent_id' => $directory->parent_id,
                'media_count' => 0,
                'children' => [],
            ],
        ]);
    }

    /**
     * Rename a directory/folder.
     */
    public function updateDirectory(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $directory = MediaDirectory::findOrFail($id);
        $directory->update([
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name').'-'.time()),
        ]);

        return response()->json([
            'message' => 'Folder renamed successfully.',
            'directory' => [
                'id' => $directory->id,
                'tenant_id' => $directory->tenant_id,
                'name' => $directory->name,
                'slug' => $directory->slug,
                'parent_id' => $directory->parent_id,
            ],
        ]);
    }

    /**
     * Delete a directory/folder.
     */
    public function destroyDirectory(int $id): JsonResponse
    {
        $directory = MediaDirectory::findOrFail($id);

        // Disassociate media inside this directory (or delete cascade)
        Media::where('directory_id', $directory->id)->update(['directory_id' => null]);

        $directory->delete();

        return response()->json(['message' => 'Folder deleted successfully.']);
    }

    /**
     * Move media file to another directory.
     */
    public function updateMediaDirectory(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'directory_id' => 'nullable|exists:media_directories,id',
        ]);

        $media = Media::findOrFail($id);
        $media->update(['directory_id' => $request->input('directory_id')]);

        return response()->json(['message' => 'File moved successfully.']);
    }
}
