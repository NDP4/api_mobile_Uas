@extends('layout')

@section('content')
<div id="app">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Orders</h1>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Order ID</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Customer</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Payment</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="order in orders" :key="order.id">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ order.id }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ order.user.fullname }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ order.total_amount }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ order.status }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ order.payment_status }}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button @click="updateStatus(order.id)" class="text-indigo-600 hover:text-indigo-900">Update Status</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue

createApp({
    data() {
        return {
            orders: []
        }
    },
    mounted() {
        this.fetchOrders()
    },
    methods: {
        fetchOrders() {
            axios.get('/api/orders')
                .then(response => {
                    this.orders = response.data.orders
                })
                .catch(error => {
                    console.error('Error fetching orders:', error)
                })
        },
        updateStatus(orderId) {
            const newStatus = prompt('Enter new status (pending/processing/shipped/delivered/cancelled):')
            if (newStatus) {
                axios.put(`/api/orders/${orderId}/status`, { status: newStatus })
                    .then(() => {
                        this.fetchOrders()
                    })
                    .catch(error => {
                        console.error('Error updating order status:', error)
                    })
            }
        }
    }
}).mount('#app')
</script>
@endsection
