<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { useToast } from '@/composables/useToast';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Company',
    href: '/company',
  },
];

const csrf = (): string | null => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Form state
const showForm = ref(false);
const formMode = ref<'create' | 'edit'>('create');
const formModel = ref<Record<string, any> | null>(null);

const form = ref({
  name: '',
  email: '',
  website: '',
  logoFile: null as File | null,
  logoPreview: null as string | null,
});

const formErrors = ref<Record<string, string[]>>({});

// Watch form model to populate form fields when editing
watch(() => formModel.value, (v) => {
  if (v) {
    form.value.name = v.name ?? '';
    form.value.email = v.email ?? '';
    form.value.website = v.website ?? '';
    form.value.logoFile = null; // reset file input
    form.value.logoPreview = v.logo ?? null; // show existing logo
  } else {
    form.value.name = '';
    form.value.email = '';
    form.value.website = '';
    form.value.logoFile = null;
    form.value.logoPreview = null;
  }
}, { immediate: true });

// Image validation function
const validateImageFile = (file: File): string | null => {
  // Check file type
  if (!file.type.match(/image\/png/i)) {
    return 'Only PNG format is allowed.';
  }
  // Check file size (2MB max)
  const maxSizeBytes = 2 * 1024 * 1024;
  if (file.size > maxSizeBytes) {
    return 'File size must not exceed 2MB.';
  }
  return null;
};

// Check image resolution
const checkImageResolution = (file: File): Promise<string | null> => {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        if (img.width < 100 || img.height < 100) {
          resolve('Minimum image resolution is 100x100 pixels.');
        } else {
          resolve(null);
        }
      };
      img.onerror = () => {
        resolve('Failed to read image. Please ensure the file is a valid PNG.');
      };
      img.src = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  });
};

const onLogoFileSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) {
    form.value.logoFile = null;
    form.value.logoPreview = null;
    formErrors.value.logo = [];
    return;
  }

  // Validate file type and size
  const typeError = validateImageFile(file);
  if (typeError) {
    showToast({ variant: 'error', title: 'Invalid file', message: typeError });
    formErrors.value.logo = [typeError];
    input.value = '';
    return;
  }

  // Check resolution
  const resError = await checkImageResolution(file);
  if (resError) {
    showToast({ variant: 'error', title: 'Invalid image', message: resError });
    formErrors.value.logo = [resError];
    input.value = '';
    return;
  }

  // All good: store file and show preview
  form.value.logoFile = file;
  formErrors.value.logo = [];

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    form.value.logoPreview = e.target?.result as string;
  };
  reader.readAsDataURL(file);
};

const companies = ref<any[]>([]);
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

const fetchCompanies = async (page = 1) => {
  loading.value = true;
  try {
    const res = await fetch(`/api/companies?page=${page}`);
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      const message = data?.message ?? firstError(data?.errors) ?? 'Unable to load companies.';
      throw new Error(message);
    }
    
    const payload: any = data ?? {};
    const rows = Array.isArray(payload.data) ? payload.data : Array.isArray(payload) ? payload : [];
    companies.value = rows;
    meta.value.current_page = payload.current_page ?? 1;
    meta.value.last_page = payload.last_page ?? 1;
    meta.value.per_page = payload.per_page ?? 5;
    meta.value.total = payload.total ?? (companies.value.length || 0);
  } catch (e) {
    showToast({
      variant: 'error',
      title: 'Load failed',
      message: e instanceof Error ? e.message : 'Unexpected error while loading companies.',
    });
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

const openNew = () => {
  formMode.value = 'create';
  formModel.value = null;
  showForm.value = true;
};

const openEdit = (item: any) => {
  formMode.value = 'edit';
  formModel.value = item;
  showForm.value = true;
};

const onSaved = () => {
  showForm.value = false;
  // refresh list to reflect changes
  fetchCompanies(meta.value.current_page);
};

const deleteCompany = async (id: number) => {
  if (!confirm('Are you sure you want to delete this company?')) return;
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const headers: Record<string, string> = { 'Accept': 'application/json' };
    if (token) headers['X-CSRF-TOKEN'] = token;
    const res = await fetch(`/api/companies/${id}`, { method: 'DELETE', headers, credentials: 'same-origin' });
    const data = await res.json().catch(() => null);
    if (res.ok) {
      showToast({ variant: 'success', title: 'Deleted', message: 'Company removed successfully.' });
      fetchCompanies(meta.value.current_page);
    } else {
      const message = data?.message ?? firstError(data?.errors) ?? 'Failed to delete company.';
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
    formData.append('website', form.value.website);
    if (form.value.logoFile) {
      formData.append('logo', form.value.logoFile);
    }

    if (formMode.value === 'create') {
      const res = await fetch('/api/companies', {
        method: 'POST',
        headers: { 'Accept': 'application/json', ...(token && { 'X-CSRF-TOKEN': token }) },
        body: formData,
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!res.ok) {
        formErrors.value = data?.errors ?? {};
        const message = data?.message ?? firstError(data?.errors) ?? 'Failed to create company.';
        showToast({ variant: 'error', title: 'Create failed', message });
        return;
      }
      showToast({ variant: 'success', title: 'Company created', message: 'Company saved successfully.' });
      onSaved();
    } else {
      const id = formModel.value?.id;
      if (!id) throw new Error('Missing company id');
      const res = await fetch(`/api/companies/${id}`, {
        method: 'PUT',
        headers: { 'Accept': 'application/json', '_method': 'PUT', ...(token && { 'X-CSRF-TOKEN': token }) },
        body: formData,
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!res.ok) {
        formErrors.value = data?.errors ?? {};
        const message = data?.message ?? firstError(data?.errors) ?? 'Failed to update company.';
        showToast({ variant: 'error', title: 'Update failed', message });
        return;
      }
      showToast({ variant: 'success', title: 'Company updated', message: 'Changes saved successfully.' });
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
  <Head title="Company" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Company</h1>
        <div>
          <button class="px-3 py-1 rounded bg-green-600 text-white" @click="openNew">New</button>
        </div>
      </div>

      <!-- Inline Form Modal -->
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="onCancel"></div>
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg z-10 w-full max-w-xl p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">{{ formMode === 'create' ? 'New Company' : 'Edit Company' }}</h3>
            <button class="text-sm text-neutral-500" @click="onCancel">Close</button>
          </div>

          <div class="grid grid-cols-1 gap-3">
            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Name</div>
              <input v-model="form.name" class="w-full rounded border px-2 py-1" />
              <div v-if="formErrors.name" class="text-red-500 text-xs mt-1">{{ formErrors.name[0] }}</div>
            </label>

            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Email</div>
              <input v-model="form.email" type="email" class="w-full rounded border px-2 py-1" />
              <div v-if="formErrors.email" class="text-red-500 text-xs mt-1">{{ formErrors.email[0] }}</div>
            </label>

            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Website</div>
              <input v-model="form.website" class="w-full rounded border px-2 py-1" />
              <div v-if="formErrors.website" class="text-red-500 text-xs mt-1">{{ formErrors.website[0] }}</div>
            </label>

            <label class="block">
              <div class="text-sm text-neutral-600 mb-1">Logo (PNG, min 100x100, max 2MB)</div>
              <input
                type="file"
                accept=".png,image/png"
                class="w-full rounded border px-2 py-1"
                @change="onLogoFileSelected"
              />
              <div v-if="formErrors.logo" class="text-red-500 text-xs mt-1">{{ formErrors.logo[0] }}</div>

              <!-- Logo Preview -->
              <div v-if="form.logoPreview" class="mt-2">
                <img :src="form.logoPreview" alt="Logo preview" class="h-16 w-16 object-contain border rounded" />
              </div>
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
              <th class="px-4 py-2">Email</th>
              <th class="px-4 py-2">Website</th>
              <th class="px-4 py-2">Logo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td class="px-4 py-6" colspan="5">Loading...</td>
            </tr>
            <tr v-for="item in companies" :key="item.id" class="border-t">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button class="p-1 text-blue-600 hover:bg-blue-50 rounded" @click.prevent="openEdit(item)" title="Edit">
                    <!-- pencil icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 0 0-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 0 0 0-2.828z"/></svg>
                  </button>
                  <button class="p-1 text-red-600 hover:bg-red-50 rounded" @click.prevent="deleteCompany(item.id)" title="Delete">
                    <!-- trash icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H3a1 1 0 100 2h14a1 1 0 100-2h-2V3a1 1 0 00-1-1H6zm2 7a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4 0a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                  </button>
                </div>
              </td>
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
