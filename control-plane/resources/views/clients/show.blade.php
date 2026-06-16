<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $client->business_name }}</h2></x-slot>
    <div class="p-6 space-y-6 max-w-2xl">
        @if (session('status'))<div class="text-green-700">{{ session('status') }}</div>@endif

        <div class="border p-4 rounded">
            <p><strong>Domain:</strong> {{ $client->primary_domain }}</p>
            <p><strong>Status:</strong> {{ $client->status }}</p>
            <p><strong>Gross sales:</strong> {{ number_format($grossTotal, 2) }}
               ({{ $ordersTotal }} orders)</p>
            <p><strong>Commission owed:</strong> {{ number_format($commissionOwed, 2) }}</p>
        </div>

        @if ($client->status === 'pending')
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <button class="bg-green-600 text-white px-4 py-2 rounded">Approve</button>
            </form>
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="reject">
                <button class="bg-red-600 text-white px-4 py-2 rounded">Reject</button>
            </form>
        @else
            <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-3">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="update">
                <label class="block">Commission type
                    <select name="commission_type" class="block border rounded w-full">
                        <option value="percent" @selected($client->commission_type==='percent')>Percent of sales</option>
                        <option value="per_order" @selected($client->commission_type==='per_order')>Flat per order</option>
                    </select>
                </label>
                <label class="block">Commission value
                    <input type="number" step="0.01" name="commission_rate" value="{{ $client->commission_rate }}"
                           class="block border rounded w-full">
                </label>
                <label class="block">Status
                    <select name="status" class="block border rounded w-full">
                        @foreach (['active','warning','locked_admin','maintenance'] as $s)
                            <option value="{{ $s }}" @selected($client->status===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
            </form>
        @endif

        <div>
            <h3 class="font-semibold">Report history</h3>
            <table class="w-full text-left border mt-2">
                <thead><tr class="bg-gray-100"><th class="p-2">Period</th><th class="p-2">Gross</th><th class="p-2">Orders</th></tr></thead>
                <tbody>
                @foreach ($client->reports->sortByDesc('period_start') as $r)
                    <tr class="border-t"><td class="p-2">{{ $r->period_start->toDateString() }}</td>
                        <td class="p-2">{{ number_format($r->gross_sales, 2) }} {{ $r->currency }}</td>
                        <td class="p-2">{{ $r->order_count }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
