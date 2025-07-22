<template>
  <div class="currency-transaction-history">
    <!-- Header with filters -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-4">
        <h3 class="text-lg font-semibold">Transaction History</h3>
        <div class="flex items-center space-x-2">
          <CurrencySelector
            v-model="selectedCurrency"
            placeholder="All currencies"
            :clearable="true"
            class="w-40"
            @change="handleCurrencyFilter"
          />
          <NSelect
            v-model:value="selectedStatus"
            :options="statusOptions"
            placeholder="All statuses"
            clearable
            class="w-40"
            @update:value="handleStatusFilter"
          />
        </div>
      </div>
      
      <div class="flex items-center space-x-2">
        <AtButton @click="exportTransactions" size="small" type="secondary">
          Export
        </AtButton>
        <AtButton @click="refreshData" size="small" type="secondary">
          Refresh
        </AtButton>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4 border">
        <div class="text-sm text-gray-600">Total Transactions</div>
        <div class="text-2xl font-bold">{{ filteredTransactions.length }}</div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-4 border">
        <div class="text-sm text-gray-600">Pending Conversions</div>
        <div class="text-2xl font-bold text-orange-600">{{ pendingConversions }}</div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-4 border">
        <div class="text-sm text-gray-600">Converted</div>
        <div class="text-2xl font-bold text-green-600">{{ convertedTransactions }}</div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-4 border">
        <div class="text-sm text-gray-600">Total Value</div>
        <div class="text-lg font-bold">{{ totalValue }}</div>
      </div>
    </div>

    <!-- Transaction Table -->
    <div class="bg-white rounded-lg shadow border overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sortBy('date')" class="flex items-center space-x-1">
                  <span>Date</span>
                  <SortIcon :direction="getSortDirection('date')" />
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Description
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Account
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sortBy('currency_code')" class="flex items-center space-x-1">
                  <span>Currency</span>
                  <SortIcon :direction="getSortDirection('currency_code')" />
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <button @click="sortBy('total')" class="flex items-center space-x-1">
                  <span>Amount</span>
                  <SortIcon :direction="getSortDirection('total')" />
                </button>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Conversion
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="transaction in paginatedTransactions" 
              :key="transaction.id" 
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(transaction.date) }}
              </td>
              
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ transaction.description }}</div>
                <div v-if="transaction.payee" class="text-sm text-gray-500">{{ transaction.payee.name }}</div>
                <div v-if="transaction.category" class="text-xs text-blue-600">{{ transaction.category.name }}</div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ transaction.account?.name }}
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center space-x-2">
                  <span class="text-sm font-medium">{{ transaction.currency_code }}</span>
                  <span v-if="transaction.currency_code !== transaction.account?.currency_code" 
                        class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                    Multi-currency
                  </span>
                </div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(transaction.total, transaction.currency_code) }}
                </div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="transaction.exchange_rate && transaction.exchange_amount" class="text-sm">
                  <div class="font-medium text-gray-900">
                    {{ formatCurrency(transaction.exchange_amount, transaction.account?.currency_code) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    Rate: {{ transaction.exchange_rate.toFixed(4) }}
                  </div>
                </div>
                <div v-else-if="needsConversion(transaction)" class="text-sm text-orange-600">
                  Pending
                </div>
                <div v-else class="text-sm text-gray-500">
                  N/A
                </div>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(transaction)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getStatusLabel(transaction) }}
                </span>
              </td>
              
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="editTransaction(transaction)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </button>
                  <button
                    v-if="needsConversion(transaction)"
                    @click="processPayment(transaction)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Convert
                  </button>
                  <button
                    @click="viewDetails(transaction)"
                    class="text-gray-600 hover:text-gray-900"
                  >
                    Details
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="previousPage"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Previous
          </button>
          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing {{ startIndex }} to {{ endIndex }} of {{ filteredTransactions.length }} results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                Previous
              </button>
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                :class="[
                  page === currentPage
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                ]"
              >
                {{ page }}
              </button>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredTransactions.length === 0" class="text-center py-12">
      <div class="text-gray-500 text-lg mb-4">No transactions found</div>
      <p class="text-gray-400 mb-6">Try adjusting your filters or create a new transaction</p>
      <AtButton @click="$emit('createTransaction')" class="text-white bg-primary">
        Create Transaction
      </AtButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { format } from 'date-fns';
import { AtButton } from 'atmosphere-ui';
import { NSelect } from 'naive-ui';

import CurrencySelector from './CurrencySelector.vue';
import SortIcon from './SortIcon.vue';

import { formatCurrency } from '../currency-constants';

interface Props {
  transactions?: any[];
  loading?: boolean;
}

interface Emits {
  (e: 'editTransaction', transaction: any): void;
  (e: 'processPayment', transaction: any): void;
  (e: 'viewDetails', transaction: any): void;
  (e: 'createTransaction'): void;
  (e: 'refresh'): void;
  (e: 'export'): void;
}

const props = withDefaults(defineProps<Props>(), {
  transactions: () => [],
  loading: false
});

const emit = defineEmits<Emits>();

// State
const selectedCurrency = ref('');
const selectedStatus = ref('');
const sortField = ref('date');
const sortDirection = ref<'asc' | 'desc'>('desc');
const currentPage = ref(1);
const itemsPerPage = ref(20);

// Options
const statusOptions = [
  { value: 'verified', label: 'Verified' },
  { value: 'pending', label: 'Pending Conversion' },
  { value: 'converted', label: 'Converted' },
  { value: 'draft', label: 'Draft' }
];

// Computed
const filteredTransactions = computed(() => {
  let filtered = [...props.transactions];

  // Filter by currency
  if (selectedCurrency.value) {
    filtered = filtered.filter(t => t.currency_code === selectedCurrency.value);
  }

  // Filter by status
  if (selectedStatus.value) {
    filtered = filtered.filter(t => {
      if (selectedStatus.value === 'pending') {
        return needsConversion(t);
      }
      if (selectedStatus.value === 'converted') {
        return t.exchange_rate && t.exchange_amount;
      }
      return t.status === selectedStatus.value;
    });
  }

  // Sort
  filtered.sort((a, b) => {
    let aValue = a[sortField.value];
    let bValue = b[sortField.value];

    if (sortField.value === 'date') {
      aValue = new Date(aValue).getTime();
      bValue = new Date(bValue).getTime();
    }

    if (sortDirection.value === 'asc') {
      return aValue > bValue ? 1 : -1;
    } else {
      return aValue < bValue ? 1 : -1;
    }
  });

  return filtered;
});

const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredTransactions.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredTransactions.value.length / itemsPerPage.value);
});

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value + 1;
});

const endIndex = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, filteredTransactions.value.length);
});

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, currentPage.value - 2);
  const end = Math.min(totalPages.value, currentPage.value + 2);
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const pendingConversions = computed(() => {
  return filteredTransactions.value.filter(t => needsConversion(t)).length;
});

const convertedTransactions = computed(() => {
  return filteredTransactions.value.filter(t => t.exchange_rate && t.exchange_amount).length;
});

const totalValue = computed(() => {
  const currencies = {};
  filteredTransactions.value.forEach(t => {
    if (!currencies[t.currency_code]) {
      currencies[t.currency_code] = 0;
    }
    currencies[t.currency_code] += t.total;
  });

  return Object.entries(currencies)
    .map(([currency, amount]) => formatCurrency(amount as number, currency))
    .join(' | ');
});

// Methods
const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

const needsConversion = (transaction: any) => {
  return transaction.currency_code !== transaction.account?.currency_code && 
         !transaction.exchange_rate;
};

const getStatusBadgeClass = (transaction: any) => {
  if (transaction.exchange_rate && transaction.exchange_amount) {
    return 'bg-green-100 text-green-800';
  }
  if (needsConversion(transaction)) {
    return 'bg-orange-100 text-orange-800';
  }
  if (transaction.status === 'verified') {
    return 'bg-blue-100 text-blue-800';
  }
  return 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (transaction: any) => {
  if (transaction.exchange_rate && transaction.exchange_amount) {
    return 'Converted';
  }
  if (needsConversion(transaction)) {
    return 'Pending Conversion';
  }
  return transaction.status || 'Verified';
};

const sortBy = (field: string) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'desc';
  }
};

const getSortDirection = (field: string) => {
  return sortField.value === field ? sortDirection.value : null;
};

const handleCurrencyFilter = (currency: any) => {
  selectedCurrency.value = currency?.code || '';
  currentPage.value = 1;
};

const handleStatusFilter = (status: string) => {
  selectedStatus.value = status || '';
  currentPage.value = 1;
};

const goToPage = (page: number) => {
  currentPage.value = page;
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const editTransaction = (transaction: any) => {
  emit('editTransaction', transaction);
};

const processPayment = (transaction: any) => {
  emit('processPayment', transaction);
};

const viewDetails = (transaction: any) => {
  emit('viewDetails', transaction);
};

const refreshData = () => {
  emit('refresh');
};

const exportTransactions = () => {
  emit('export');
};

// Watchers
watch(() => props.transactions, () => {
  currentPage.value = 1;
});
</script>

<style scoped>
.currency-transaction-history {
  padding: 1rem;
}

.sort-button {
  display: flex;
  align-items: center;
  space-x: 0.25rem;
}
</style>