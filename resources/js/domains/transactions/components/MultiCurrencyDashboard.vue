<template>
  <div class="multi-currency-dashboard">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Multi-Currency Accounts</h2>
      <AtButton @click="showAccountWizard = true" type="primary" class="text-white bg-primary">
        Add Multi-Currency Account
      </AtButton>
    </div>

    <!-- Account Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div
        v-for="account in multiCurrencyAccounts"
        :key="account.id"
        class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow"
      >
        <!-- Account Header -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ account.name }}</h3>
            <span class="text-sm opacity-90">{{ account.account_type }}</span>
          </div>
          <div class="text-sm opacity-90 mt-1">
            Primary: {{ account.currency_code }}
          </div>
        </div>

        <!-- Currency Balances -->
        <div class="p-4">
          <!-- Primary Currency Balance -->
          <div class="mb-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-600">Primary Balance</span>
              <span class="text-lg font-bold text-gray-900">
                {{ formatCurrency(account.primary_balance || 0, account.currency_code) }}
              </span>
            </div>
          </div>

          <!-- Secondary Currency Balances -->
          <div v-if="account.currency_balances && account.currency_balances.length > 0" class="space-y-2">
            <div class="text-sm font-medium text-gray-600 border-t pt-2">Secondary Balances</div>
            <div
              v-for="balance in account.currency_balances"
              :key="balance.currency_code"
              class="flex items-center justify-between text-sm"
            >
              <span class="text-gray-600">{{ balance.currency_code }}</span>
              <div class="text-right">
                <div class="font-medium">
                  {{ formatCurrency(balance.balance, balance.currency_code) }}
                </div>
                <div v-if="balance.pending_balance && balance.pending_balance > 0" class="text-xs text-orange-600">
                  Pending: {{ formatCurrency(balance.pending_balance, balance.currency_code) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Account Actions -->
          <div class="flex space-x-2 mt-4 pt-4 border-t">
            <AtButton 
              @click="openTransactionModal(account)" 
              size="small" 
              type="primary"
              class="flex-1 text-white bg-primary"
            >
              Add Transaction
            </AtButton>
            <AtButton 
              @click="openPaymentModal(account)" 
              size="small" 
              type="secondary"
              class="flex-1"
            >
              Make Payment
            </AtButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Multi-Currency Transactions -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Recent Multi-Currency Transactions</h3>
          <div class="flex space-x-2">
            <CurrencySelector
              v-model="filterCurrency"
              placeholder="Filter by currency"
              :clearable="true"
              class="w-40"
              @change="handleCurrencyFilter"
            />
            <AtButton @click="refreshTransactions" size="small" type="secondary">
              Refresh
            </AtButton>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Description
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Account
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
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
            <tr v-for="transaction in filteredTransactions" :key="transaction.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(transaction.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ transaction.description }}</div>
                <div v-if="transaction.payee" class="text-sm text-gray-500">{{ transaction.payee.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ transaction.account?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(transaction.total, transaction.currency_code) }}
                </div>
                <div v-if="transaction.exchange_amount && transaction.exchange_rate" class="text-sm text-gray-500">
                  {{ formatCurrency(transaction.exchange_amount, transaction.account?.currency_code) }}
                  (Rate: {{ transaction.exchange_rate.toFixed(4) }})
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusBadgeClass(transaction.status)"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
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
                    v-if="transaction.currency_code !== transaction.account?.currency_code && !transaction.exchange_rate"
                    @click="processPayment(transaction)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Process Payment
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="filteredTransactions.length === 0" class="text-center py-8">
          <div class="text-gray-500">No multi-currency transactions found</div>
          <AtButton @click="openTransactionModal()" class="mt-4 text-white bg-primary">
            Create First Transaction
          </AtButton>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <MultiCurrencyAccountWizard
      :show="showAccountWizard"
      @close="showAccountWizard = false"
      @saved="handleAccountSaved"
    />

    <MultiCurrencyTransactionModal
      :show="showTransactionModal"
      :transaction-data="selectedTransaction"
      :accounts="accounts"
      :categories="categories"
      @close="closeTransactionModal"
      @saved="handleTransactionSaved"
    />

    <MultiCurrencyPaymentModal
      :show="showPaymentModal"
      :account="selectedAccount"
      :transaction="selectedTransaction"
      @close="closePaymentModal"
      @saved="handlePaymentSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { format } from 'date-fns';
import { AtButton } from 'atmosphere-ui';

import CurrencySelector from './CurrencySelector.vue';
import MultiCurrencyAccountWizard from './MultiCurrencyAccountWizard.vue';
import MultiCurrencyTransactionModal from './MultiCurrencyTransactionModal.vue';
import MultiCurrencyPaymentModal from './MultiCurrencyPaymentModal.vue';

import { formatCurrency } from '../currency-constants';

interface Props {
  accounts?: any[];
  transactions?: any[];
  categories?: any[];
}

interface Emits {
  (e: 'accountCreated', account: any): void;
  (e: 'transactionCreated', transaction: any): void;
  (e: 'paymentProcessed', payment: any): void;
  (e: 'refresh'): void;
}

const props = withDefaults(defineProps<Props>(), {
  accounts: () => [],
  transactions: () => [],
  categories: () => []
});

const emit = defineEmits<Emits>();

// State
const showAccountWizard = ref(false);
const showTransactionModal = ref(false);
const showPaymentModal = ref(false);
const selectedAccount = ref(null);
const selectedTransaction = ref(null);
const filterCurrency = ref('');

// Computed
const multiCurrencyAccounts = computed(() => {
  return props.accounts.filter(account => account.is_multi_currency);
});

const filteredTransactions = computed(() => {
  let transactions = props.transactions.filter(t => t.is_multi_currency);
  
  if (filterCurrency.value) {
    transactions = transactions.filter(t => t.currency_code === filterCurrency.value);
  }
  
  return transactions.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
});

// Methods
const formatDate = (date: string) => {
  return format(new Date(date), 'MMM dd, yyyy');
};

const getStatusBadgeClass = (status: string) => {
  const classes = {
    'verified': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'converted': 'bg-blue-100 text-blue-800',
    'draft': 'bg-gray-100 text-gray-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (transaction: any) => {
  if (transaction.exchange_rate) {
    return 'Converted';
  }
  if (transaction.currency_code !== transaction.account?.currency_code) {
    return 'Pending Conversion';
  }
  return transaction.status || 'Verified';
};

const openTransactionModal = (account = null) => {
  selectedAccount.value = account;
  selectedTransaction.value = null;
  showTransactionModal.value = true;
};

const openPaymentModal = (account: any) => {
  selectedAccount.value = account;
  selectedTransaction.value = null;
  showPaymentModal.value = true;
};

const editTransaction = (transaction: any) => {
  selectedTransaction.value = transaction;
  selectedAccount.value = null;
  showTransactionModal.value = true;
};

const processPayment = (transaction: any) => {
  selectedTransaction.value = transaction;
  selectedAccount.value = transaction.account;
  showPaymentModal.value = true;
};

const closeTransactionModal = () => {
  showTransactionModal.value = false;
  selectedTransaction.value = null;
  selectedAccount.value = null;
};

const closePaymentModal = () => {
  showPaymentModal.value = false;
  selectedTransaction.value = null;
  selectedAccount.value = null;
};

const handleAccountSaved = (account: any) => {
  emit('accountCreated', account);
  emit('refresh');
};

const handleTransactionSaved = (transaction: any) => {
  emit('transactionCreated', transaction);
  emit('refresh');
};

const handlePaymentSaved = (payment: any) => {
  emit('paymentProcessed', payment);
  emit('refresh');
};

const handleCurrencyFilter = (currency: any) => {
  filterCurrency.value = currency?.code || '';
};

const refreshTransactions = () => {
  emit('refresh');
};

onMounted(() => {
  // Initial data load if needed
});
</script>

<style scoped>
.multi-currency-dashboard {
  padding: 1rem;
}

.account-card {
  transition: all 0.2s ease-in-out;
}

.account-card:hover {
  transform: translateY(-2px);
}

.status-badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
}
</style>