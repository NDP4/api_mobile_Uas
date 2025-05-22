@extends('layout')

@section('content')
<div id="app">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Order Management</h1>
            <p class="mt-2 text-sm text-gray-700">A list of all orders with their status and payment information.</p>
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
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">#@{{ order.id }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ order.user.fullname }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">Rp @{{ formatPrice(order.total_amount) }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span :class="getStatusClass(order.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        @{{ order.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span :class="getPaymentStatusClass(order.payment_status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        @{{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button @click="openStatusModal(order)" class="text-indigo-600 hover:text-indigo-900">Update Status</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Update Order Status</h3>
                <div class="mt-5">
                    <label class="block text-sm font-medium text-gray-700">Order #@{{ selectedOrder?.id }}</label>
                    <select v-model="newStatus" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option v-for="status in statuses" :key="status" :value="status">
                            @{{ status }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button @click="updateStatus" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                    Update
                </button>
                <button @click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue

createApp({
    data() {
        return {
            orders: [],
            selectedOrder: null,
            showModal: false,
            newStatus: '',
            statuses: ['pending', 'processing', 'shipped', 'delivered', 'cancelled']
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
        formatPrice(price) {
            return new Intl.NumberFormat('id-ID').format(price)
        },
        getStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                processing: 'bg-blue-100 text-blue-800',
                shipped: 'bg-indigo-100 text-indigo-800',
                delivered: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800'
            }
            return classes[status] || 'bg-gray-100 text-gray-800'
        },
        getPaymentStatusClass(status) {
            const classes = {
                unpaid: 'bg-red-100 text-red-800',
                pending: 'bg-yellow-100 text-yellow-800',
                paid: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                expired: 'bg-gray-100 text-gray-800'
            }
            return classes[status] || 'bg-gray-100 text-gray-800'
        },
        openStatusModal(order) {
            this.selectedOrder = order
            this.newStatus = order.status
            this.showModal = true
        },
        closeModal() {
            this.showModal = false
            this.selectedOrder = null
            this.newStatus = ''
        },
        updateStatus() {
            if (!this.selectedOrder || !this.newStatus) return

            axios.put(`/api/orders/${this.selectedOrder.id}/status`, { status: this.newStatus })
                .then(() => {
                    this.fetchOrders()
                    this.closeModal()
                })
                .catch(error => {
                    console.error('Error updating order status:', error)
                    alert('Failed to update order status')
                })
        }
    }
}).mount('#app')
</script>
@endsection
