<div x-data="{open: false}">

    <h2 class="mb-4 text-2xl font-medium">Child Component</h2>

    <button class="px-4 py-1 bg-gray-200 rounded" x-on:click="open = !open">Open Me</button>

    <div x-show="open">
        Hello I'm not hiding anymore!
    </div>
</div>
