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
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Category</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Price</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stock</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Weight</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="product in products" :key="product.id">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.title }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.category }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.price }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.main_stock }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ product.weight }}g</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span :class="{'text-green-600': product.status === 'available', 'text-red-600': product.status === 'unavailable'}">
                                        @{{ product.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <button @click="editProduct(product)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
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
    <div v-if="showAddModal || showEditModal" class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <form @submit.prevent="showEditModal ? submitEditForm() : submitAddForm()">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Basic Information -->
                        <div class="col-span-2">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" v-model="formData.title" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea v-model="formData.description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <input type="text" v-model="formData.category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Weight (grams)</label>
                            <input type="number" v-model="formData.weight" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Price and Stock -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price</label>
                            <input type="number" v-model="formData.price" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Discount (%)</label>
                            <input type="number" v-model="formData.discount" min="0" max="100" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Main Stock</label>
                            <input type="number" v-model="formData.main_stock" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select v-model="formData.status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>

                        <!-- Variants -->
                        <div class="col-span-2">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Variants</h3>
                            <div class="space-y-4">
                                <div v-for="(variant, index) in formData.variants" :key="index" class="grid grid-cols-4 gap-4 p-4 border border-gray-200 rounded-md">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <input type="text" v-model="variant.name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Price</label>
                                        <input type="number" v-model="variant.price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                                        <input type="number" v-model="variant.stock" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Discount (%)</label>
                                        <div class="flex items-center mt-1">
                                            <input type="number" v-model="variant.discount" min="0" max="100" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <button type="button" @click="removeVariant(index)" class="ml-2 text-red-600 hover:text-red-900">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="addVariant" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Add Variant
                                </button>
                            </div>
                        </div>

                        <!-- Product Images -->
                        <div class="col-span-2">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Product Images</h3>
                            <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                            <span>Upload files</span>
                                            <input type="file" class="sr-only" multiple @change="handleFileUpload" accept="image/*">
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <!-- Preview Images -->
                            <div v-if="previewImages.length > 0" class="mt-4 grid grid-cols-4 gap-4">
                                <div v-for="(image, index) in previewImages" :key="index" class="relative">
                                    <img :src="image" class="h-24 w-24 object-cover rounded-md">
                                    <button type="button" @click="removeImage(index)" class="absolute top-0 right-0 -mt-2 -mr-2 bg-red-600 text-white rounded-full p-1 hover:bg-red-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
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
createApp({
    data() {
        return {
            products: [],
            showAddModal: false,
            showEditModal: false,
            selectedFiles: [],
            previewImages: [],
            formData: {
                title: '',
                description: '',
                category: '',
                price: 0,
                discount: 0,
                main_stock: 0,
                weight: 0,
                status: 'available',
                variants: [],
                images: []
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
        addVariant() {
            this.formData.variants.push({
                name: '',
                price: 0,
                stock: 0,
                discount: 0
            })
        },
        removeVariant(index) {
            this.formData.variants.splice(index, 1)
        },
        handleFileUpload(event) {
            const files = event.target.files
            this.selectedFiles = [...files]

            // Clear previous previews
            this.previewImages = []

            // Create previews
            for (let file of files) {
                const reader = new FileReader()
                reader.onload = (e) => {
                    this.previewImages.push(e.target.result)
                }
                reader.readAsDataURL(file)
            }
        },
        removeImage(index) {
            this.previewImages.splice(index, 1)
            this.selectedFiles.splice(index, 1)
        },
        submitAddForm() {
            const formData = new FormData()

            // Append basic product data
            Object.keys(this.formData).forEach(key => {
                if (key !== 'variants' && key !== 'images') {
                    formData.append(key, this.formData[key])
                }
            })

            // Append variants
            if (this.formData.variants.length > 0) {
                formData.append('variants', JSON.stringify(this.formData.variants))
            }

            // Append images
            this.selectedFiles.forEach(file => {
                formData.append('images[]', file)
            })

            axios.post('/api/products', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                this.fetchProducts()
                this.closeModal()
            })
            .catch(error => {
                console.error('Error creating product:', error)
            })
        },
        editProduct(product) {
            this.formData = {
                ...product,
                variants: product.variants || []
            }
            this.showEditModal = true

            // Reset file upload states
            this.selectedFiles = []
            this.previewImages = []

            // Add existing images to preview
            if (product.images) {
                this.previewImages = product.images.map(img => img.image_url)
            }
        },
        submitEditForm() {
            const formData = new FormData()

            // Append basic product data
            Object.keys(this.formData).forEach(key => {
                if (key !== 'variants' && key !== 'images') {
                    formData.append(key, this.formData[key])
                }
            })

            // Append variants
            if (this.formData.variants.length > 0) {
                formData.append('variants', JSON.stringify(this.formData.variants))
            }

            // Append new images
            this.selectedFiles.forEach(file => {
                formData.append('images[]', file)
            })

            axios.put(`/api/products/${this.formData.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                this.fetchProducts()
                this.closeModal()
            })
            .catch(error => {
                console.error('Error updating product:', error)
            })
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
        closeModal() {
            this.showAddModal = false
            this.showEditModal = false
            this.formData = {
                title: '',
                description: '',
                category: '',
                price: 0,
                discount: 0,
                main_stock: 0,
                weight: 0,
                status: 'available',
                variants: [],
                images: []
            }
            this.selectedFiles = []
            this.previewImages = []
        }
    }
}).mount('#app')
</script>
@endsection
