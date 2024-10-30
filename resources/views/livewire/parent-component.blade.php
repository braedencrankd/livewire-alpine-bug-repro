<div>
    {{-- Care about people's approval and you will be their prisoner. --}}

    <h2 class="mb-4 text-2xl font-medium">Parent Component</h2>

    <div class="flex items-center gap-2 mb-4">
        <button class="px-4 py-1 bg-gray-200 rounded" wire:click="increment">+</button>
        <p class="text-2xl font-medium">{{ $count }}</p>
        <button class="px-4 py-1 bg-gray-200 rounded" wire:click="decrement">-</button>
    </div>

    <livewire:child-component lazy />
</div>
