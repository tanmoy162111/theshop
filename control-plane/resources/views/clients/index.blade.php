<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Clients</h2></x-slot>
    <div class="p-6">
        @if (session('status'))<div class="mb-4 text-green-700">{{ session('status') }}</div>@endif
        <table class="w-full text-left border">
            <thead><tr class="bg-gray-100">
                <th class="p-2">Business</th><th class="p-2">Domain</th><th class="p-2">Status</th>
                <th class="p-2">Commission</th><th class="p-2">Gross</th><th class="p-2">Owed</th>
                <th class="p-2">Last seen</th><th class="p-2"></th>
            </tr></thead>
            <tbody>
            @foreach ($clients as $c)
                <tr class="border-t">
                    <td class="p-2">{{ $c->business_name }}</td>
                    <td class="p-2">{{ $c->primary_domain }}</td>
                    <td class="p-2">{{ $c->status }}</td>
                    <td class="p-2">{{ $c->commission_type }} {{ $c->commission_rate }}</td>
                    <td class="p-2">{{ number_format($c->gross_total ?? 0, 2) }}</td>
                    <td class="p-2">{{ number_format($c->commission_owed, 2) }}</td>
                    <td class="p-2">{{ optional($c->last_seen_at)->diffForHumans() ?? '—' }}</td>
                    <td class="p-2"><a class="text-blue-600" href="{{ route('clients.show', $c) }}">Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
