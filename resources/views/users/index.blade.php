@extends('layout')

@section('content')
<div id="app">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-xl font-semibold text-gray-900">Users</h1>
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
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Name</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Phone</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Address</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="user in users" :key="user.id">
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.fullname }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.email }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.phone }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">@{{ user.address }}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
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
            users: []
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
        }
    }
}).mount('#app')
</script>
@endsection
