<!-- src/views/sales/SalesInvoiceList.vue -->
<template>
  <div class="page-content">
    <div class="page-header">
      <div class="header-actions">
        <button class="btn btn-primary" @click="navigateToCreateInvoice">
          <i class="fas fa-plus-circle"></i> Buat Invoice Baru
        </button>
        <button class="btn btn-secondary" @click="navigateToCreateFromOrder">
          <i class="fas fa-file-invoice"></i> Buat dari Order
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Daftar Sales Invoice</h3>
        <search-filter
          placeholder="Cari invoice..."
          v-model:value="searchQuery"
          @search="handleSearch"
          @clear="clearSearch"
        >
          <template v-slot:filters>
            <div class="filter-group">
              <label>Status</label>
              <select v-model="filters.status" @change="handleSearch">
                <option value="">Semua Status</option>
                <option value="Draft">Draft</option>
                <option value="Open">Open</option>
                <option value="Paid">Paid</option>
                <option value="Canceled">Canceled</option>
                <option value="Closed">Closed</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Tanggal Awal</label>
              <input type="date" v-model="filters.startDate" @change="handleSearch" />
            </div>
            <div class="filter-group">
              <label>Tanggal Akhir</label>
              <input type="date" v-model="filters.endDate" @change="handleSearch" />
            </div>
          </template>
        </search-filter>
      </div>

      <div class="card-body">
        <div v-if="isLoading" class="loading-indicator">
          <i class="fas fa-spinner fa-spin"></i> Memuat data...
        </div>

        <div v-else-if="filteredInvoices.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-file-invoice"></i>
          </div>
          <h3>Tidak ada Invoice</h3>
          <p>Tidak ada data invoice yang tersedia sesuai dengan kriteria pencarian.</p>
        </div>

        <div v-else>
          <table class="data-table">
            <thead>
              <tr>
                <th @click="sortBy('invoice_number')" class="sortable">
                  Nomor Invoice
                  <i v-if="sortColumn === 'invoice_number'"
                     :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                </th>
                <th @click="sortBy('invoice_date')" class="sortable">
                  Tanggal Invoice
                  <i v-if="sortColumn === 'invoice_date'"
                     :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                </th>
                <th @click="sortBy('customer.name')" class="sortable">
                  Pelanggan
                  <i v-if="sortColumn === 'customer.name'"
                     :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                </th>
                <th @click="sortBy('due_date')" class="sortable">
                  Tanggal Jatuh Tempo
                  <i v-if="sortColumn === 'due_date'"
                     :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                </th>
                <th @click="sortBy('total_amount')" class="sortable">
                  Total
                  <i v-if="sortColumn === 'total_amount'"
                     :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                </th>
                <th @click="sortBy('status')" class="sortable">
                  Status
                  <i v-if="sortColumn === 'status'"
                     :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
                </th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="invoice in paginatedInvoices" :key="invoice.invoice_id">
                <td>{{ invoice.invoice_number }}</td>
                <td>{{ formatDate(invoice.invoice_date) }}</td>
                <td>{{ invoice.customer ? invoice.customer.name : 'N/A' }}</td>
                <td>{{ formatDate(invoice.due_date) }}</td>
                <td>{{ formatCurrency(invoice.total_amount, invoice.currency_code || 'IDR') }}</td>
                <td>
                  <span :class="getStatusClass(invoice.status)">
                    {{ invoice.status }}
                  </span>
                </td>
                <td class="actions-cell">
                  <button class="btn-icon" @click="viewInvoice(invoice)" title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button
                    class="btn-icon"
                    @click="editInvoice(invoice)"
                    title="Edit"
                    :disabled="!canEdit(invoice)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button
                    class="btn-icon"
                    @click="printInvoice(invoice)"
                    title="Cetak">
                    <i class="fas fa-print"></i>
                  </button>
                  <button
                    class="btn-icon"
                    @click="viewPaymentInfo(invoice)"
                    title="Info Pembayaran">
                    <i class="fas fa-money-bill-wave"></i>
                  </button>
                  <button
                    class="btn-icon btn-danger"
                    @click="confirmDelete(invoice)"
                    title="Hapus"
                    :disabled="!canDelete(invoice)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <pagination-component
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="paginationFrom"
            :to="paginationTo"
            :total="filteredInvoices.length"
            @page-changed="changePage"
          />
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <confirmation-modal
      v-if="showDeleteConfirmation"
      title="Konfirmasi Hapus"
      :message="`Apakah Anda yakin ingin menghapus invoice <strong>${invoiceToDelete?.invoice_number}</strong>?<br>Tindakan ini tidak dapat dibatalkan.`"
      confirm-button-text="Hapus"
      confirm-button-class="btn btn-danger"
      @confirm="deleteInvoice"
      @close="cancelDelete"
    />
  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';

export default {
  name: 'SalesInvoiceList',
  setup() {
    const router = useRouter();
    const invoices = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref('');
    const filters = ref({
      status: '',
      startDate: '',
      endDate: ''
    });
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const sortColumn = ref('invoice_date');
    const sortDirection = ref('desc');
    const showDeleteConfirmation = ref(false);
    const invoiceToDelete = ref(null);

    // Fetch invoices from API
        const fetchInvoices = async () => {
          isLoading.value = true;
          try {
            const response = await axios.get('sales/invoices');
            invoices.value = response.data.data;
          } catch (error) {
            console.error('Error fetching invoices:', error);
            alert('Gagal memuat data invoice. Silakan coba lagi.');
          } finally {
            isLoading.value = false;
          }
        };

    // Filter invoices based on search and filters
    const filteredInvoices = computed(() => {
      let result = [...invoices.value];

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(invoice =>
          invoice.invoice_number.toLowerCase().includes(query) ||
          (invoice.customer && invoice.customer.name.toLowerCase().includes(query))
        );
      }

      // Apply status filter
      if (filters.value.status) {
        result = result.filter(invoice => invoice.status === filters.value.status);
      }

      // Apply date filters
      if (filters.value.startDate) {
        result = result.filter(invoice =>
          new Date(invoice.invoice_date) >= new Date(filters.value.startDate)
        );
      }
      if (filters.value.endDate) {
        result = result.filter(invoice =>
          new Date(invoice.invoice_date) <= new Date(filters.value.endDate)
        );
      }

      // Apply sorting
      result.sort((a, b) => {
        let valA, valB;

        // Handle nested properties like customer.name
        if (sortColumn.value.includes('.')) {
          const parts = sortColumn.value.split('.');
          valA = a[parts[0]] ? a[parts[0]][parts[1]] : '';
          valB = b[parts[0]] ? b[parts[0]][parts[1]] : '';
        } else {
          valA = a[sortColumn.value];
          valB = b[sortColumn.value];
        }

        // Handle dates
        if (sortColumn.value === 'invoice_date' || sortColumn.value === 'due_date') {
          valA = new Date(valA);
          valB = new Date(valB);
        }

        // Compare based on direction
        if (sortDirection.value === 'asc') {
          return valA > valB ? 1 : -1;
        } else {
          return valA < valB ? 1 : -1;
        }
      });

      return result;
    });

    // Pagination
    const totalPages = computed(() =>
      Math.ceil(filteredInvoices.value.length / itemsPerPage.value)
    );

    const paginatedInvoices = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value;
      const end = start + itemsPerPage.value;
      return filteredInvoices.value.slice(start, end);
    });

    const paginationFrom = computed(() =>
      filteredInvoices.value.length === 0
        ? 0
        : (currentPage.value - 1) * itemsPerPage.value + 1
    );

    const paginationTo = computed(() =>
      Math.min(currentPage.value * itemsPerPage.value, filteredInvoices.value.length)
    );

    // Reset to first page when filters change
    watch([searchQuery, filters], () => {
      currentPage.value = 1;
    });

    // Format utilities
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    };

    const formatCurrency = (amount, currency = 'IDR') => {
      if (amount === undefined || amount === null) return 'N/A';
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount);
    };

    // Status styling
    const getStatusClass = (status) => {
      switch (status) {
        case 'Draft': return 'status-badge status-draft';
        case 'Open': return 'status-badge status-open';
        case 'Paid': return 'status-badge status-paid';
        case 'Canceled': return 'status-badge status-canceled';
        case 'Closed': return 'status-badge status-closed';
        default: return 'status-badge';
      }
    };

    // Navigation actions
    const viewInvoice = (invoice) => {
      router.push(`/sales/invoices/${invoice.invoice_id}`);
    };

    const editInvoice = (invoice) => {
      router.push(`/sales/invoices/${invoice.invoice_id}/edit`);
    };

    const printInvoice = (invoice) => {
      router.push(`/sales/invoices/${invoice.invoice_id}/print`);
    };

    const viewPaymentInfo = (invoice) => {
      router.push(`/sales/invoices/${invoice.invoice_id}/payment-info`);
    };

    const navigateToCreateInvoice = () => {
      router.push('/sales/invoices/create');
    };

    const navigateToCreateFromOrder = () => {
      router.push('/sales/invoices/create-from-order');
    };

    // Permissions
    const canEdit = (invoice) => {
      return !['Paid', 'Closed', 'Canceled'].includes(invoice.status);
    };

    const canDelete = (invoice) => {
      return !['Paid', 'Closed'].includes(invoice.status);
    };

    // Deletion handling
    const confirmDelete = (invoice) => {
      invoiceToDelete.value = invoice;
      showDeleteConfirmation.value = true;
    };

    const cancelDelete = () => {
      invoiceToDelete.value = null;
      showDeleteConfirmation.value = false;
    };

    const deleteInvoice = async () => {
      if (!invoiceToDelete.value) return;

      try {
        await axios.delete(`sales/invoices/${invoiceToDelete.value.invoice_id}`);
        fetchInvoices(); // Refresh the list
        showDeleteConfirmation.value = false;
        invoiceToDelete.value = null;
      } catch (error) {
        console.error('Error deleting invoice:', error);
        if (error.response && error.response.data.message) {
          alert(`Gagal menghapus invoice: ${error.response.data.message}`);
        } else {
          alert('Gagal menghapus invoice. Silakan coba lagi.');
        }
      }
    };

    // Sorting
    const sortBy = (column) => {
      if (sortColumn.value === column) {
        // Toggle direction if same column
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
      } else {
        // Set new column and default to ascending
        sortColumn.value = column;
        sortDirection.value = 'asc';
      }
    };

    // Search handling
    const handleSearch = () => {
      currentPage.value = 1; // Reset to first page
    };

    const clearSearch = () => {
      searchQuery.value = '';
      filters.value = {
        status: '',
        startDate: '',
        endDate: ''
      };
      currentPage.value = 1;
    };

    // Pagination
    const changePage = (page) => {
      currentPage.value = page;
    };

    // Initialize
    onMounted(() => {
      fetchInvoices();
    });

    return {
      invoices,
      isLoading,
      searchQuery,
      filters,
      currentPage,
      sortColumn,
      sortDirection,
      showDeleteConfirmation,
      invoiceToDelete,
      filteredInvoices,
      paginatedInvoices,
      totalPages,
      paginationFrom,
      paginationTo,
      formatDate,
      formatCurrency,
      getStatusClass,
      viewInvoice,
      editInvoice,
      printInvoice,
      viewPaymentInfo,
      navigateToCreateInvoice,
      navigateToCreateFromOrder,
      canEdit,
      canDelete,
      confirmDelete,
      cancelDelete,
      deleteInvoice,
      sortBy,
      handleSearch,
      clearSearch,
      changePage
    };
  }
};
</script>

<style scoped>
.page-content {
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
}

.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.card-header h3 {
  margin: 0 0 1rem 0;
  font-size: 1.25rem;
  color: var(--gray-800);
}

.card-body {
  padding: 1.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-secondary {
  background-color: var(--gray-100);
  border-color: var(--gray-300);
  color: var(--gray-700);
}

.btn-secondary:hover {
  background-color: var(--gray-200);
}

.btn-danger {
  color: var(--danger-color);
}

.btn-danger:hover {
  background-color: rgba(220, 38, 38, 0.1);
}

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.375rem;
  background: none;
  border: none;
  color: var(--gray-600);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background-color: var(--gray-100);
  color: var(--gray-800);
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th, .data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.data-table th {
  font-weight: 500;
  color: var(--gray-600);
  background-color: var(--gray-50);
}

.data-table tbody tr:hover {
  background-color: var(--gray-50);
}

.sortable {
  cursor: pointer;
  white-space: nowrap;
}

.sortable:hover {
  background-color: var(--gray-100);
}

.actions-cell {
  white-space: nowrap;
  text-align: right;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-draft {
  background-color: var(--gray-200);
  color: var(--gray-700);
}

.status-open {
  background-color: #e0f2fe;
  color: #0369a1;
}

.status-paid {
  background-color: #dcfce7;
  color: #16a34a;
}

.status-canceled {
  background-color: #fee2e2;
  color: #dc2626;
}

.status-closed {
  background-color: #f3f4f6;
  color: #1f2937;
}

.loading-indicator, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 0;
  text-align: center;
}

.loading-indicator i {
  font-size: 2rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.empty-icon {
  font-size: 3rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: var(--gray-700);
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: var(--gray-500);
  max-width: 20rem;
}

@media (max-width: 768px) {
  .data-table {
    font-size: 0.825rem;
  }

  .data-table th, .data-table td {
    padding: 0.5rem;
  }

  .actions-cell .btn-icon {
    width: 1.75rem;
    height: 1.75rem;
  }
}
</style>
