@extends('layout')

@section('content')
<div id="app">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Banners</h1>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button @click="showAddModal = true" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                Add Banner
            </button>
        </div>
    </div>

    <!-- Banners Table -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Title</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Images</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="banner in banners" :key="banner.id">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ banner.title }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ banner.status }}</td>
                                <td class="px-3 py-4 text-sm text-gray-500">
                                    <div class="flex space-x-2">
                                        <img v-for="image in banner.images" :key="image.id" :src="image.image_url" class="h-12 w-12 object-cover rounded">
                                    </div>
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button @click="editBanner(banner)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button @click="deleteBanner(banner.id)" class="text-red-600 hover:text-red-900">Delete</button>
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
                            @{{ showEditModal ? 'Edit Banner' : 'Add New Banner' }}
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" v-model="formData.title" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select v-model="formData.status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Images</label>
                                <input type="file" @change="handleFileUpload" multiple accept="image/*" class="mt-1 block w-full">
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
            banners: [],
            showAddModal: false,
            showEditModal: false,
            formData: {
                title: '',
                status: 'active',
                images: []
            }
        }
    },
    mounted() {
        this.fetchBanners()
    },
    methods: {
        fetchBanners() {
            axios.get('/api/banners')
                .then(response => {
                    this.banners = response.data.banners
                })
                .catch(error => {
                    console.error('Error fetching banners:', error)
                })
        },
        handleFileUpload(event) {
            this.formData.images = event.target.files
        },
        editBanner(banner) {
            this.formData = {
                id: banner.id,
                title: banner.title,
                status: banner.status
            }
            this.showEditModal = true
        },
        deleteBanner(id) {
            if (confirm('Are you sure you want to delete this banner?')) {
                axios.delete(`/api/banners/${id}`)
                    .then(() => {
                        this.fetchBanners()
                    })
                    .catch(error => {
                        console.error('Error deleting banner:', error)
                    })
            }
        },
        submitForm() {
            const formData = new FormData()
            formData.append('title', this.formData.title)
            formData.append('status', this.formData.status)

            if (this.formData.images) {
                Array.from(this.formData.images).forEach((file, index) => {
                    formData.append(`images[${index}]`, file)
                })
            }

            if (this.showEditModal) {
                axios.put(`/api/banners/${this.formData.id}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                    .then(() => {
                        this.fetchBanners()
                        this.closeModal()
                    })
                    .catch(error => {
                        console.error('Error updating banner:', error)
                    })
            } else {
                axios.post('/api/banners', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                    .then(() => {
                        this.fetchBanners()
                        this.closeModal()
                    })
                    .catch(error => {
                        console.error('Error creating banner:', error)
                    })
            }
        },
        closeModal() {
            this.showAddModal = false
            this.showEditModal = false
            this.formData = {
                title: '',
                status: 'active',
                images: []
            }
        }
    }
}).mount('#app')
</script>
@endsection
