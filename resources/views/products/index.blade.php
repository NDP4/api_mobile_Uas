@extends('layout')

@section('content')
<div id="app">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Products</h1>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button @click="showAddModal = true" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                Add Product
            </button>
        </div>
    </div>

    <!-- Products Table -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Title</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Price</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stock</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="product in products" :key="product.id">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.title }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.price }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.main_stock }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.status }}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button @click="editProduct(product)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button @click="deleteProduct(product.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || showEditModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form @submit.prevent="submitForm">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            @{{ showEditModal ? 'Edit Product' : 'Add New Product' }}
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" v-model="formData.title" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea v-model="formData.description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price</label>
                                    <input type="number" v-model="formData.price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Stock</label>
                                    <input type="number" v-model="formData.main_stock" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select v-model="formData.status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            @{{ showEditModal ? 'Update' : 'Create' }}
                        </button>
                        <button @click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
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
            products: [],
            showAddModal: false,
            showEditModal: false,
            formData: {
                title: '',
                description: '',
                price: '',
                main_stock: '',
                status: 'active'
            }
        }
    },
    mounted() {
        this.fetchProducts()
    },
    methods: {
        fetchProducts() {
            axios.get('/api/products')
                .then(response => {
                    this.products = response.data.products
                })
                .catch(error => {
                    console.error('Error fetching products:', error)
                })
        },
        editProduct(product) {
            this.formData = { ...product }
            this.showEditModal = true
        },
        deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                axios.delete(`/api/products/${id}`)
                    .then(() => {
                        this.fetchProducts()
                    })
                    .catch(error => {
                        console.error('Error deleting product:', error)
                    })
            }
        },
        submitForm() {
            if (this.showEditModal) {
                axios.put(`/api/products/${this.formData.id}`, this.formData)
                    .then(() => {
                        this.fetchProducts()
                        this.closeModal()
                    })
                    .catch(error => {
                        console.error('Error updating product:', error)
                    })
            } else {
                axios.post('/api/products', this.formData)
                    .then(() => {
                        this.fetchProducts()
                        this.closeModal()
                    })
                    .catch(error => {
                        console.error('Error creating product:', error)
                    })
            }
        },
        closeModal() {
            this.showAddModal = false
            this.showEditModal = false
            this.formData = {
                title: '',
                description: '',
                price: '',
                main_stock: '',
                status: 'active'
            }
        }
    }
}).mount('#app')
</script>
@endsection
