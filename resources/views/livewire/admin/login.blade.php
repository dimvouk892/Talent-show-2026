<div class="card w-full max-w-md mx-auto p-5 sm:p-8">
    <h1 class="text-xl sm:text-2xl font-bold text-center mb-6">Σύνδεση Διαχειριστή</h1>
    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="admin-email" class="block text-sm font-medium mb-1">Email</label>
            <input id="admin-email" type="email" wire:model="email" class="input-touch" required autocomplete="email">
            @error('email') <span class="text-red-600 text-sm" role="alert">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="admin-password" class="block text-sm font-medium mb-1">Κωδικός</label>
            <input id="admin-password" type="password" wire:model="password" class="input-touch" required autocomplete="current-password">
        </div>
        <label class="flex items-center gap-3 min-h-11 text-sm">
            <input type="checkbox" wire:model="remember" class="w-5 h-5 rounded">
            Να με θυμάσαι
        </label>
        <button type="submit" class="w-full btn-touch bg-indigo-600 text-white hover:bg-indigo-700 focus-visible:ring-indigo-500">
            Σύνδεση
        </button>
    </form>
</div>
