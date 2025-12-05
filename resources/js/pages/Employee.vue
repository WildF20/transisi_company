<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employee',
    href: '/employee',
  },
];
</script>

<template>
  <Head title="Employee" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <h1 class="text-2xl font-semibold">Employee</h1>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Add any page-specific styles here */
</style>
