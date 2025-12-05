<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Company',
    href: '/company',
  },
];

const companies = ref<any[]>([]);
const loading = ref(false);
const meta = ref({ current_page: 1, last_page: 1, per_page: 5, total: 0 });

const fetchCompanies = async (page = 1) => {
  loading.value = true;
  try {
    const res = await fetch(`/api/companies?page=${page}`);
    if (!res.ok) throw new Error('Network response not ok');
    const data = await res.json();
    
    companies.value = data.data ?? data;
    meta.value.current_page = data.current_page ?? 1;
    meta.value.last_page = data.last_page ?? 1;
    meta.value.per_page = data.per_page ?? 5;
    meta.value.total = data.total ?? (companies.value.length || 0);
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => fetchCompanies(1));

const goToPage = (page: number) => {
  if (page < 1 || page > meta.value.last_page) return;
  fetchCompanies(page);
};
</script>

<template>
  <Head title="Company" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <h1 class="text-2xl font-semibold mb-4">Company</h1>

      <div class="overflow-x-auto bg-white dark:bg-[#0b1220] rounded-lg border border-sidebar-border/70 p-2">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr class="text-left text-sm text-neutral-600 dark:text-neutral-300">
              <th class="px-4 py-2">*</th>
              <th class="px-4 py-2">Name</th>
              <th class="px-4 py-2">Email</th>
              <th class="px-4 py-2">Website</th>
              <th class="px-4 py-2">Logo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td class="px-4 py-6" colspan="4">Loading...</td>
            </tr>
            <tr v-for="item in companies" :key="item.id" class="border-t">
              <td class="px-4 py-3"><span></span></td>
              <td class="px-4 py-3">{{ item.name }}</td>
              <td class="px-4 py-3">{{ item.email }}</td>
              <td class="px-4 py-3">
                <a :href="item.website" target="_blank" rel="noopener noreferrer" class="text-indigo-600">{{ item.website }}</a>
              </td>
              <td class="px-4 py-3">
                <img v-if="item.logo" :src="item.logo" alt="logo" class="h-8 w-8 object-contain" />
                <span v-else class="text-sm text-neutral-500">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-neutral-600 dark:text-neutral-300">Showing page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</div>
        <div class="flex items-center gap-2">
          <button class="px-3 py-1 rounded border" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">Prev</button>
          <template v-for="p in Math.min(5, meta.last_page)" :key="p">
            <button
              v-if="meta.last_page <= 5"
              class="px-3 py-1 rounded border"
              :class="{ 'bg-gray-200': p === meta.current_page }"
              @click="goToPage(p)">
              {{ p }}
            </button>
          </template>
          <button class="px-3 py-1 rounded border" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">Next</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Add any page-specific styles here */
</style>
