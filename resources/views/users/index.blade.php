@extends('layout')

@section('content')
<div id="app">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Users</h1>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button @click="showAddModal = true" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                Add User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Avatar</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Phone</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Address</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">City</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Province</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Postal Code</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="user in users" :key="user.id">
                                <td class="whitespace-nowrap px-3 py-4">
                                    <img v-if="user.avatar" :src="'https://apilumenmobileuas.ndp.my.id/' + user.avatar" class="h-10 w-10 rounded-full" />
                                    <div v-else class="h-10 w-10 rounded-full bg-gray-200"></div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.fullname }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.email }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.phone }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.address }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.city }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.province }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.postal_code }}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button @click="editUser(user)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div v-if="showAddModal || showEditModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        @{{ showAddModal ? 'Add New User' : 'Edit User' }}
                    </h3>
                    <div class="mt-4">
                        <form @submit.prevent="showAddModal ? submitAddForm() : submitEditForm()">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Avatar</label>
                                <input type="file" @change="handleFileUpload" accept="image/*" class="mt-1">
                                <img v-if="previewImage" :src="previewImage" class="mt-2 h-20 w-20 rounded-full">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" v-model="formData.fullname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div class="mb-4" v-if="showAddModal">
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" v-model="formData.email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div class="mb-4" v-if="showAddModal">
                                <label class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="password" v-model="formData.password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" v-model="formData.phone" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <input type="text" v-model="formData.address" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" v-model="formData.city" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Province</label>
                                <input type="text" v-model="formData.province" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                <input type="text" v-model="formData.postal_code" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                                    @{{ showAddModal ? 'Add' : 'Save Changes' }}
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
    </div>
</div>

<script>
const { createApp } = Vue

createApp({
    data() {
        return {
            users: [],
            showAddModal: false,
            showEditModal: false,
            formData: {
                fullname: '',
                email: '',
                password: '',
                phone: '',
                address: '',
                city: '',
                province: '',
                postal_code: ''
            },
            selectedFile: null,
            previewImage: null,
            editingUserId: null
        }
    },
    mounted() {
        this.fetchUsers()
    },
    methods: {
        fetchUsers() {
            axios.get('/api/users')
                .then(response => {
                    this.users = response.data.users
                })
                .catch(error => {
                    console.error('Error fetching users:', error)
                })
        },
        handleFileUpload(event) {
            this.selectedFile = event.target.files[0]
            if (this.selectedFile) {
                this.previewImage = URL.createObjectURL(this.selectedFile)
            }
        },
        submitAddForm() {
            const formData = new FormData()
            Object.keys(this.formData).forEach(key => {
                if (this.formData[key]) {
                    formData.append(key, this.formData[key])
                }
            })
            if (this.selectedFile) {
                formData.append('avatar', this.selectedFile)
            }

            axios.post('/api/register', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(() => {
                this.fetchUsers()
                this.closeModal()
            })
            .catch(error => {
                console.error('Error adding user:', error)
            })
        },
        submitEditForm() {
            const formData = {}
            Object.keys(this.formData).forEach(key => {
                if (this.formData[key] && key !== 'email' && key !== 'password') {
                    formData[key] = this.formData[key]
                }
            })

            const updatePromise = axios.put(`/api/users/${this.editingUserId}`, formData)

            let avatarPromise = Promise.resolve()
            if (this.selectedFile) {
                const avatarData = new FormData()
                avatarData.append('avatar', this.selectedFile)
                avatarData.append('user_id', this.editingUserId)
                avatarPromise = axios.post('/uploads/avatars', avatarData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
            }

            Promise.all([updatePromise, avatarPromise])
                .then(() => {
                    this.fetchUsers()
                    this.closeModal()
                })
                .catch(error => {
                    console.error('Error updating user:', error)
                })
        },
        editUser(user) {
            this.formData = {
                fullname: user.fullname,
                phone: user.phone,
                address: user.address,
                city: user.city,
                province: user.province,
                postal_code: user.postal_code
            }
            this.editingUserId = user.id
            this.previewImage = user.avatar
            this.showEditModal = true
        },
        deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                axios.delete(`/api/users/${id}`)
                    .then(() => {
                        this.fetchUsers()
                    })
                    .catch(error => {
                        console.error('Error deleting user:', error)
                    })
            }
        },
        closeModal() {
            this.showAddModal = false
            this.showEditModal = false
            this.formData = {
                fullname: '',
                email: '',
                password: '',
                phone: '',
                address: '',
                city: '',
                province: '',
                postal_code: ''
            }
            this.selectedFile = null
            this.previewImage = null
            this.editingUserId = null
        }
    }
}).mount('#app')
</script>
@endsection
