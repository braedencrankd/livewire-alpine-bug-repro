<div class="w-full antialiased"
	x-cloak
	x-data="() => ({
    isDragging: false,
    isDropped: false,
    isLoading: false,

    onDrop(e) {
        this.isDropped = true;
        this.isDragging = false;

        const file = @js($multiple) ? e.dataTransfer.files : e.dataTransfer.files[0];
        const args = ['upload', file,
            () => { this.isLoading = false; },
            (error) => {
                console.log('livewire-dropzone upload error', error);
                alert('An error occurred while uploading');
            },
            () => { this.isLoading = true; }
        ];

        @js($multiple) ? $wire.uploadMultiple(...args) : $wire.upload(...args);
    },
    onDragenter() {
        this.isDragging = true;
    },
    onDragleave() {
        this.isDragging = false;
    },
    onDragover() {
        this.isDragging = true;
    },
    removeUpload(tmpFilename) {
        $wire.dispatch(@js($uuid) + ':fileRemoved', {
            tmpFilename
        });
    }
})"
	@dragenter.prevent.document="onDragenter($event)"
	@dragleave.prevent="onDragleave($event)"
	@dragover.prevent="onDragover($event)"
	@drop.prevent="onDrop">


	<div class="flex flex-col items-start justify-center w-full h-full"
		{{-- x-show="isDragging" --}}>

		@if (!is_null($error))
			<div class="w-full p-4 mb-4 rounded bg-red-50 dark:bg-red-600">
				<div class="flex items-start gap-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0a9 9 0 0 1 18 0"/></svg>
					<h3 class="text-sm font-medium text-red-800 dark:text-red-100">{{ $error }}</h3>
				</div>
			</div>
		@endif

		<div class="w-full">
			<div class="w-full border border-blue-200 border-dashed rounded cursor-pointer"
				@click="$refs.input.click()">
				<div>
					<div class="flex items-center justify-center h-full gap-2 py-8 bg-gray-50 dark:bg-gray-700"
						x-show="!isLoading">
						
						<p class="text-gray-600 text-md dark:text-gray-400">Drop here or <span
								class="font-semibold text-black dark:text-white">Browse files</span></p>
					</div>

					<div class="flex items-center justify-center h-full gap-2 py-8 bg-gray-50 dark:bg-gray-700"
						x-show="isLoading">
						<label class="hidden"
							for="upload">{{ __('Upload files') }} </label>
						<div role="status">
                            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
							<span class="sr-only">Loading...</span>
						</div>
					</div>
				</div>
				<input class="hidden"
					id="upload"
					x-ref="input"
					wire:model="upload"
					type="file"
					x-on:livewire-upload-start="isLoading = true"
					x-on:livewire-upload-finish="isLoading = false"
					x-on:livewire-upload-error="console.log('x-on:livewire-dropzone upload error', error)"
					@if (!is_null($this->accept)) accept="{{ $this->accept }}" @endif
					@if ($multiple === true) multiple @endif>
			</div>
			<div class="flex items-center gap-2 mt-2 text-xs text-gray-700">
				@php
					$hasMaxFileSize = !is_null($this->maxFileSize);
					$hasMimes = !empty($this->mimes);
				@endphp

				@if ($hasMaxFileSize)
					<p>{{ __('Up to :size', ['size' => \Illuminate\Support\Number::fileSize($this->maxFileSize * 1024)]) }}</p>
				@endif

				@if ($hasMimes)
					<p class="text-xs text-gray-500">{{ Str::upper($this->mimesDispaly) }}</p>
				@endif
			</div>
		</div>

		@if ($showPreview && $files && count($files) > 0)
			<div class="flex flex-wrap justify-start w-full mt-5 gap-x-10 gap-y-2">
				@foreach ($files as $file)
					<div
						class="flex items-center justify-between w-full h-auto gap-2 overflow-hidden border border-gray-200 rounded dark:border-gray-700">

						<div class="flex items-center gap-3">
							<div class="flex items-center justify-center bg-gray-100 h-14 w-14 dark:bg-gray-700">
                                <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><rect width="15" height="18.5" x="4.5" y="2.75" rx="3.5"/><path d="M8.5 6.755h7m-7 4h7m-7 4H12"/></g></svg>
							</div>
							<div class="flex flex-col items-start gap-1">
								<div class="max-w-[300px] truncate text-left text-sm font-medium text-slate-900 dark:text-slate-100">
									{{ $file['name'] }}</div>
								<div class="text-sm font-medium text-center text-gray-500">
									{{ \Illuminate\Support\Number::fileSize($file['size']) }}</div>
							</div>
						</div>

						<div class="flex items-center mr-3">
							<button type="button"
								@click="removeUpload('{{ $file['tmpFilename'] }}')">
								<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
							</button>
						</div>
					</div>
				@endforeach
			</div>
		@endif

	</div>
</div>
