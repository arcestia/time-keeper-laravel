<div id="tab-jobs" class="mt-10 hidden">
    <h3 class="text-lg font-semibold">Create Job</h3>
    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
            <div>
                <label class="block text-sm">Key</label>
                <input id="job-key" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g. city_cleaner">
            </div>
            <div>
                <label class="block text-sm">Name</label>
                <input id="job-name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="City Cleaner">
            </div>
            <div>
                <label class="block text-sm">Description</label>
                <textarea id="job-desc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3" placeholder="Short description..."></textarea>
            </div>
        </div>
        <div class="space-y-2">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm">Duration (s)</label>
                    <input id="job-duration" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="600">
                </div>
                <div>
                    <label class="block text-sm">Reward (s)</label>
                    <input id="job-reward" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="900">
                </div>
                <div>
                    <label class="block text-sm">Cooldown (s)</label>
                    <input id="job-cooldown" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="1800">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-sm">Energy Cost (%)</label>
                    <input id="job-energy" type="number" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="10">
                </div>
                <div class="flex items-center gap-2">
                    <input id="job-active" type="checkbox" class="rounded border-gray-300" checked>
                    <label for="job-active" class="text-sm">Active</label>
                </div>
                <div class="text-right">
                    <button id="job-create" type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">Create</button>
                </div>
            </div>
            <div id="job-status" class="text-sm text-gray-500"></div>
        </div>
    </div>
</div>
