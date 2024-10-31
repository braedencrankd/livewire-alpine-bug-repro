<?php

namespace App\Livewire;



use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ChildComponent extends Component
{
    use WithFileUploads;

    #[Modelable]
    public ?array $files;

    #[Locked]
    public array $rules;

    #[Locked]
    public string $uuid;

    public $upload;

    public string $error;

    public bool $multiple;

    public bool $showPreview = true;

    public function rules(): array
    {
        $field = $this->multiple ? 'upload.*' : 'upload';

        return [
            $field => [...$this->rules],
        ];
    }

    public function mount(array $rules = [], bool $multiple = false): void
    {
        $this->uuid = Str::uuid();
        $this->multiple = $multiple;
        $this->rules = $rules;
        $this->files = [];
    }

    public function updatedUpload(): void
    {

        $this->reset('error');

        // TODO, this is a bug, if you allow text/csv files into the rules this still fails
        // Attempted to resolve this by adding the rules and custom messages to the validate method below

        // $rules =  $this->rules();
        // $rules = array_map(function ($rule) {
        //     return is_array($rule) ? implode('|', $rule) : $rule;
        // }, $rules);
        // $messages = [];
        // foreach ($rules as $key => $rule) {
        //     $messages[$key . '.mimes'] = 'The ' . $key . ' must be a file of type: ' . $this->mimes;
        //     $messages[$key . '.max'] = 'The ' . $key . ' may not be greater than ' . $this->maxFileSize;
        // }
        //  try {
        // $validate = $this->validate($rules, $messages);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            // This is a workaround to the validate method not working as expected
            $message = $e->validator->errors()->first();
            if (strpos($message, 'upload.0') !== false) {
                $upload = $this->upload[0];
                $mimeType = $upload->getMimeType();
                if (!$this->isMime($mimeType)) {
                    $this->dispatch("{$this->uuid}:uploadError", $e->getMessage() . ' - ' . $mimeType);
                    return;
                }
            }
        }


        $this->upload = $this->multiple
            ? $this->upload
            : [$this->upload];

        foreach ($this->upload as $upload) {
            $this->handleUpload($upload);
        }

        $this->reset('upload');
    }

    /**
     * Handle the uploaded file and dispatch an event with file details.
     */
    public function handleUpload(TemporaryUploadedFile $file): void
    {
        $this->dispatch("{$this->uuid}:fileAdded", [
            'tmpFilename' => $file->getFilename(),
            'name' => $file->getClientOriginalName(),
            'extension' => $file->extension(),
            'path' => $file->path(),
            'temporaryUrl' => $file->isPreviewable() ? $file->temporaryUrl() : null,
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Handle the file added event.
     */
    #[On('{uuid}:fileAdded')]
    public function onFileAdded(array $file): void
    {
        $this->files = $this->multiple ? array_merge($this->files, [$file]) : [$file];
        // $this->dispatch('file-added', uuid: $this->uuid, file: $file);
        $this->dispatch('files-updated');
    }

    /**
     * Handle the file removal event.
     */
    #[On('{uuid}:fileRemoved')]
    public function onFileRemoved(string $tmpFilename): void
    {
        $this->files = array_filter($this->files, function ($file) use ($tmpFilename) {
            // Remove the temporary file from the array only.
            // No need to remove from the Livewire's temporary upload directory manually.
            // Because, files older than 24 hours cleanup automatically by Livewire.
            // For more details, refer to: https://livewire.laravel.com/docs/uploads#configuring-automatic-file-cleanup
            return $file['tmpFilename'] !== $tmpFilename;
        });

        $this->dispatch('files-updated');
    }

    /**
     * Handle the upload error event.
     */
    #[On('{uuid}:uploadError')]
    public function onUploadError(string $error): void
    {
        $this->error = $error;
    }

    #[Computed]
    public function mimesDispaly()
    {
        $mimes = collect($this->rules)
            ->filter(fn($rule) => str_starts_with($rule, 'mimes:'))
            ->flatMap(fn($rule) => explode(',', substr($rule, strpos($rule, ':') + 1)))
            ->map(function ($mime) {
                switch ($mime) {
                    case 'png':
                        return 'PNG';
                    case 'jpg':
                        return 'JPEG';
                    case 'pdf':
                        return 'PDF';
                    case 'webp':
                        return 'WebP';
                    case 'application/zip':
                        return 'ZIP';
                    case 'application/pdf':
                        return 'PDF';
                    case 'pptx':
                        return 'PowerPoint';
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                        return 'Word Document';
                    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                        return 'PowerPoint';
                        // APPLICATION/VND.OPENXMLFORMATS-OFFICEDOCUMENT.SPREADSHEETML.SHEET
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                        return 'Excel';
                        // APPLICATION/VND.MS-EXCEL
                    case 'application/vnd.ms-excel':
                    case 'video/mp4':
                        return 'MP4';
                    case 'video/quicktime':
                        return 'QuickTime';
                    case 'image/avif':
                        return 'AVIF';
                    case 'image/webp':
                        return 'WebP';
                    case 'image/svg+xml':
                        return 'SVG';
                    case 'text/html':
                        return 'HTML';
                    default:
                        return $mime;
                }
            })
            ->unique()
            ->values()
            ->values()
            ->join(', ');

        return $mimes;
    }

    /**
     * Retrieve the MIME types from the rules.
     */
    #[Computed]
    public function mimes()
    {
        return collect($this->rules)
            ->filter(fn($rule) => str_starts_with($rule, 'mimes:'))
            ->flatMap(fn($rule) => explode(',', substr($rule, strpos($rule, ':') + 1)))
            ->unique()
            ->values()
            ->join(', ');
    }

    /**
     * Get the accepted file extensions based on MIME types.
     */
    #[Computed]
    public function accept(): ?string
    {
        return !empty($this->mimes) ? collect(explode(', ', $this->mimes))->map(fn($mime) => '.' . $mime)->implode(',') : null;
    }

    /**
     * Get the maximum file size in a human-readable format.
     */
    #[Computed]
    public function maxFileSize(): ?string
    {
        return collect($this->rules)
            ->filter(fn($rule) => str_starts_with($rule, 'max:'))
            ->flatMap(fn($rule) => explode(',', substr($rule, strpos($rule, ':') + 1)))
            ->unique()
            ->values()
            ->first();
    }

    /**
     * Checks if the provided MIME type corresponds to an image.
     */
    public function isMime($mimeType): bool
    {
        return in_array($mimeType, explode(', ', $this->mimes));
    }
    public function render()
    {
        return view('livewire.child-component');
    }
}
