<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { useToast } from '@/composables/useToast';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employee',
    href: '/employee',
  },
];

const csrf = (): string | null => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Form state
const showForm = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const formModel = ref<Record<string, any> | null>(null);

const form = ref({
  name: '',
  company_id: '',
  email: ''
});

const formErrors = ref<Record<string, string[]>>({});

// company dropdown options
const companyOptions = ref<Array<{id: number; name: string}>>([]);

const fetchCompanyList = async () => {
  try {
    const res = await fetch('/api/list?param=company');
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      const message = data?.message ?? 'Unable to load companies list.';
      throw new Error(message);
    }
    companyOptions.value = Array.isArray(data) ? data : [];
  } catch (e) {
    showToast({ variant: 'error', title: 'Load failed', message: e instanceof Error ? e.message : 'Failed to load company list.' });
    console.error(e);
    companyOptions.value = [];
  }
};

// Watch form model to populate form fields when editing
watch(() => formModel.value, (v) => {
  if (v) {
    form.value.name = v.name ?? '';
    form.value.email = v.email ?? '';
    form.value.company_id = v.company_id ?? '';
  } else {
    form.value.name = '';
    form.value.email = '';
    form.value.company_id = '';
  }
}, { immediate: true });

const employees = ref<any[]>([]);
const loading = ref(false);
const meta = ref({ current_page: 1, last_page: 1, per_page: 5, total: 0 });
const { showToast } = useToast();

const firstError = (errors?: Record<string, string[] | string>) => {
  if (!errors) return null;
  const [firstKey] = Object.keys(errors);
  if (!firstKey) return null;
  const value = errors[firstKey];
  return Array.isArray(value) ? value[0] : value;
};

const fetchEmployees = async (page = 1) => {
  loading.value = true;
  try {
    const res = await fetch(`/api/employees?page=${page}`);
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      const message = data?.message ?? firstError(data?.errors) ?? 'Unable to load employees.';
      throw new Error(message);
    }
    
    const payload: any = data ?? {};
    const rows = Array.isArray(payload.data) ? payload.data : Array.isArray(payload) ? payload : [];
    employees.value = rows;
    meta.value.current_page = payload.current_page ?? 1;
    meta.value.last_page = payload.last_page ?? 1;
    meta.value.per_page = payload.per_page ?? 5;
    meta.value.total = payload.total ?? (employees.value.length || 0);
  } catch (e) {
    showToast({
      variant: 'error',
      title: 'Load failed',
      message: e instanceof Error ? e.message : 'Unexpected error while loading employees.',
    });
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => fetchEmployees(1));

const goToPage = (page: number) => {
  if (page < 1 || page > meta.value.last_page) return;
  fetchEmployees(page);
};

const openNew = () => {
  formMode.value = 'create';
  formModel.value = null;
  // fetch companies every time form shown
  fetchCompanyList();
  showForm.value = true;
};

const openEdit = (item: any) => {
  formMode.value = 'edit';
  formModel.value = item;
  // ensure company list is available when editing
  fetchCompanyList();
  showForm.value = true;
};

const onSaved = () => {
  showForm.value = false;
  // refresh list to reflect changes
  fetchEmployees(meta.value.current_page);
};

const deleteEmployee = async (id: number) => {
  if (!confirm('Are you sure you want to delete this employee?')) return;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const headers: Record<string, string> = { 'Accept': 'application/json' };
    if (token) headers['X-CSRF-TOKEN'] = token;
    const res = await fetch(`/api/employees/${id}`, { method: 'DELETE', headers, credentials: 'same-origin' });
    const data = await res.json().catch(() => null);
    if (res.ok) {
      showToast({ variant: 'success', title: 'Deleted', message: 'Employee removed successfully.' });
      fetchEmployees(meta.value.current_page);
    } else {
      const message = data?.message ?? firstError(data?.errors) ?? 'Failed to delete employee.';
      showToast({ variant: 'error', title: 'Delete failed', message });
    }
  } catch (e) {
    showToast({
      variant: 'error',
      title: 'Request failed',
      message: e instanceof Error ? e.message : 'Unexpected error while deleting.',
    });
    console.error(e);
  }
};

const formSave = async () => {
  formErrors.value = {};
  const token = csrf();
  const headers: Record<string, string> = { 'Accept': 'application/json' };
  if (token) headers['X-CSRF-TOKEN'] = token;

  try {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('email', form.value.email);
    formData.append('company_id', form.value.company_id);

    if (formMode.value === 'create') {
      const res = await fetch('/api/employees', {
        method: 'POST',
        headers: { 'Accept': 'application/json', ...(token && { 'X-CSRF-TOKEN': token }) },
        body: formData,
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!res.ok) {
        formErrors.value = data?.errors ?? {};
        const message = data?.message ?? firstError(data?.errors) ?? 'Failed to create employee.';
        showToast({ variant: 'error', title: 'Create failed', message });
        return;
      }
      showToast({ variant: 'success', title: 'Employee created', message: 'Employee saved successfully.' });
      onSaved();
    } else {
      const id = formModel.value?.id;
      if (!id) throw new Error('Missing employee id');
      const res = await fetch(`/api/employees/${id}`, {
        method: 'PUT',
        headers: { 'Accept': 'application/json', '_method': 'PUT', ...(token && { 'X-CSRF-TOKEN': token }) },
        body: formData,
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!res.ok) {
        formErrors.value = data?.errors ?? {};
        const message = data?.message ?? firstError(data?.errors) ?? 'Failed to update employee.';
        showToast({ variant: 'error', title: 'Update failed', message });
        return;
      }
      showToast({ variant: 'success', title: 'Employee updated', message: 'Changes saved successfully.' });
      onSaved();
    }
  } catch (e) {
    showToast({
      variant: 'error',
      title: 'Request failed',
      message: e instanceof Error ? e.message : 'Unexpected error occurred.',
    });
    console.error(e);
  }
};

const onCancel = () => {
  showForm.value = false;
  formErrors.value = {};
};
</script>

<template>
  <Head title="Employee" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Employee</h1>
        <div>
          <button class="px-3 py-1 rounded bg-green-600 text-white" @click="openNew">New</button>
        </div>
      </div>

      <!-- Inline Form Modal -->
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="onCancel"></div>
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg z-10 w-full max-w-xl p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ formMode === 'create' ? 'New Employee' : 'Edit Employee' }}</h3>
            <button class="text-sm text-neutral-500" @click="onCancel">Close</button>
          </div>

          <div class="grid grid-cols-1 gap-3">
            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Name</div>
              <input v-model="form.name" class="w-full rounded border px-2 py-1" />
              <div v-if="formErrors.name" class="text-red-500 text-xs mt-1">{{ formErrors.name[0] }}</div>
            </label>

            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Company</div>
              <select v-model="form.company_id" class="w-full text-blackrounded border px-2 py-1">
                <option value="">-- Select company --</option>
                <option v-for="c in companyOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <div v-if="formErrors.company_id" class="text-red-500 text-xs mt-1">{{ formErrors.company_id[0] }}</div>
            </label>

            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Email</div>
              <input v-model="form.email" type="email" class="w-full rounded border px-2 py-1" />
              <div v-if="formErrors.email" class="text-red-500 text-xs mt-1">{{ formErrors.email[0] }}</div>
            </label>
          </div>

          <div class="mt-4 flex justify-end gap-2">
            <button class="px-3 py-1 rounded border" @click="onCancel">Cancel</button>
            <button class="px-3 py-1 rounded bg-indigo-600 text-white" @click="formSave">
              {{ formMode === 'create' ? 'Create' : 'Update' }}
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-[#0b1220] rounded-lg border border-sidebar-border/70 p-2">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr class="text-left text-sm text-neutral-600 dark:text-neutral-300">
              <th class="px-4 py-2"> </th>
              <th class="px-4 py-2">Name</th>
              <th class="px-4 py-2">Company</th>
              <th class="px-4 py-2">Email</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td class="px-4 py-6" colspan="5">Loading...</td>
            </tr>
            <tr v-for="item in employees" :key="item.id" class="border-t">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button class="p-1 text-blue-600 hover:bg-blue-50 rounded" @click.prevent="openEdit(item)" title="Edit">
                    <!-- pencil icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 0 0-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 0 0 0-2.828z"/></svg>
                  </button>
                  <button class="p-1 text-red-600 hover:bg-red-50 rounded" @click.prevent="deleteEmployee(item.id)" title="Delete">
                    <!-- trash icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h14a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 7a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4 0a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                  </button>
                </div>
              </td>
              <td class="px-4 py-3">{{ item.name }}</td>
              <td class="px-4 py-3">{{ item.company.name }}</td>
              <td class="px-4 py-3">{{ item.email }}</td>
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
