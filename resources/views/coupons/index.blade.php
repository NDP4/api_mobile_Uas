@extends('layout')

@section('content')
<div id="app" v-cloak>
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Coupons Management</h1>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button @click="showAddModal = true" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                Add Coupon
            </button>
        </div>
    </div>

    <!-- Coupons Table -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Code</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Value</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Max Uses</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Used</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="coupon in coupons" :key="coupon.id">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ coupon.code }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ coupon.type }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ coupon.value }}@{{ coupon.type === 'percentage' ? '%' : '' }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ coupon.max_uses }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ coupon.times_used }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span :class="[
                                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        coupon.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    ]">
                                        @{{ coupon.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <button @click="editCoupon(coupon)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                    <button @click="deleteCoupon(coupon.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || showEditModal" class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form @submit.prevent="showEditModal ? submitEditForm() : submitAddForm()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Coupon Code</label>
                            <input type="text" v-model="formData.code" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select v-model="formData.type" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Value</label>
                            <input type="number" v-model="formData.value" required min="0" :max="formData.type === 'percentage' ? 100 : null" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Maximum Uses</label>
                            <input type="number" v-model="formData.max_uses" required min="1" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select v-model="formData.status" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            @{{ showEditModal ? 'Update' : 'Create' }}
                        </button>
                        <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp } = Vue

createApp({
    data() {
        return {
            coupons: [],
            showAddModal: false,
            showEditModal: false,
            formData: {
                code: '',
                type: 'fixed',
                value: 0,
                max_uses: 1,
                status: 'active'
            }
        }
    },
    mounted() {
        this.fetchCoupons()
    },
    methods: {
        fetchCoupons() {
            axios.get('/api/coupons')
                .then(response => {
                    this.coupons = response.data.coupons
                })
                .catch(error => {
                    console.error('Error fetching coupons:', error)
                })
        },
        submitAddForm() {
            axios.post('/api/coupons', this.formData)
                .then(response => {
                    this.fetchCoupons()
                    this.closeModal()
                    alert('Coupon created successfully')
                })
                .catch(error => {
                    console.error('Error creating coupon:', error)
                    alert('Failed to create coupon: ' + (error.response?.data?.message || error.message))
                })
        },
        editCoupon(coupon) {
            this.formData = { ...coupon }
            this.showEditModal = true
        },
        submitEditForm() {
            axios.put(`/api/coupons/${this.formData.id}`, this.formData)
                .then(response => {
                    this.fetchCoupons()
                    this.closeModal()
                    alert('Coupon updated successfully')
                })
                .catch(error => {
                    console.error('Error updating coupon:', error)
                    alert('Failed to update coupon: ' + (error.response?.data?.message || error.message))
                })
        },
        deleteCoupon(id) {
            if (confirm('Are you sure you want to delete this coupon?')) {
                axios.delete(`/api/coupons/${id}`)
                    .then(() => {
                        this.fetchCoupons()
                        alert('Coupon deleted successfully')
                    })
                    .catch(error => {
                        console.error('Error deleting coupon:', error)
                        alert('Failed to delete coupon: ' + (error.response?.data?.message || error.message))
                    })
            }
        },
        closeModal() {
            this.showAddModal = false
            this.showEditModal = false
            this.formData = {
                code: '',
                type: 'fixed',
                value: 0,
                max_uses: 1,
                status: 'active'
            }
        }
    }
}).mount('#app')
</script>
@endsection
