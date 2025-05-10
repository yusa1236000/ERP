<!-- src/views/sales/SalesInvoiceDetail.vue -->
<template>
  <div class="page-content">
    <div class="page-header">
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Kembali
      </button>
      <div class="header-actions">
        <button
          v-if="canEdit"
          class="btn btn-primary"
          @click="editInvoice">
          <i class="fas fa-edit"></i> Edit
        </button>
        <button class="btn btn-outline" @click="printInvoice">
          <i class="fas fa-print"></i> Cetak
        </button>
        <button class="btn btn-outline" @click="viewPaymentInfo">
          <i class="fas fa-money-bill-wave"></i> Info Pembayaran
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="loading-container">
      <div class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Memuat data...</span>
      </div>
    </div>

    <div v-else-if="error" class="error-container">
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
      </div>
      <button class="btn btn-primary" @click="fetchInvoice">Coba Lagi</button>
    </div>

    <div v-else class="invoice-detail-container">
      <!-- Invoice Header Card -->
      <div class="card">
        <div class="card-header">
          <div class="invoice-header">
            <h2>Detail Invoice</h2>
            <div class="invoice-status" :class="statusClass">
              {{ invoice.status }}
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="invoice-summary">
            <div class="row">
              <div class="col">
                <div class="info-group">
                  <div class="info-label">Nomor Invoice:</div>
                  <div class="info-value">{{ invoice.invoice_number }}</div>
                </div>
                <div class="info-group">
                  <div class="info-label">Tanggal Invoice:</div>
                  <div class="info-value">{{ formatDate(invoice.invoice_date) }}</div>
                </div>
                <div class="info-group">
                  <div class="info-label">Jatuh Tempo:</div>
                  <div class="info-value">{{ formatDate(invoice.due_date) }}</div>
                </div>
              </div>
              <div class="col">
                <div class="info-group">
                  <div class="info-label">Pelanggan:</div>
                  <div class="info-value">{{ customerName }}</div>
                </div>
                <div class="info-group">
                  <div class="info-label">No. Sales Order:</div>
                  <div class="info-value">
                    <a @click="viewSalesOrder" class="link">{{ salesOrderNumber }}</a>
                  </div>
                </div>
                <div class="info-group">
                  <div class="info-label">Mata Uang:</div>
                  <div class="info-value">{{ invoice.currency_code || 'IDR' }}</div>
                </div>
              </div>
              <div class="col">
                <div class="info-group">
                  <div class="info-label">Subtotal:</div>
                  <div class="info-value">{{ formatCurrency(calculateSubtotal) }}</div>
                </div>
                <div class="info-group">
                  <div class="info-label">Total Pajak:</div>
                  <div class="info-value">{{ formatCurrency(invoice.tax_amount) }}</div>
                </div>
                <div class="info-group">
                  <div class="info-label">Total:</div>
                  <div class="info-value total-value">{{ formatCurrency(invoice.total_amount) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Items Detail Card -->
      <div class="card">
        <div class="card-header">
          <h3>Detail Item</h3>
        </div>
        <div class="card-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode Item</th>
                <th>Deskripsi</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Pajak</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in invoiceLines" :key="line.line_id">
                <td>{{ line.item?.item_code || 'N/A' }}</td>
                <td>{{ line.item?.name || 'N/A' }}</td>
                <td class="text-right">{{ formatQuantity(line.quantity) }}</td>
                <td class="text-right">{{ formatCurrency(line.unit_price) }}</td>
                <td class="text-right">{{ formatCurrency(line.discount) }}</td>
                <td class="text-right">{{ formatCurrency(line.subtotal) }}</td>
                <td class="text-right">{{ formatCurrency(line.tax) }}</td>
                <td class="text-right">{{ formatCurrency(line.total) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" class="text-right font-bold">Total:</td>
                <td class="text-right font-bold">{{ formatCurrency(calculateSubtotal) }}</td>
                <td class="text-right font-bold">{{ formatCurrency(invoice.tax_amount) }}</td>
                <td class="text-right font-bold">{{ formatCurrency(invoice.total_amount) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Payment Information Summary -->
      <div class="card">
        <div class="card-header">
          <h3>Ringkasan Pembayaran</h3>
          <button class="btn btn-sm btn-outline" @click="viewPaymentInfo">
            Lihat Detail <i class="fas fa-arrow-right"></i>
          </button>
        </div>
        <div class="card-body">
          <div v-if="!receivables || receivables.length === 0" class="no-data">
            <p>Tidak ada informasi pembayaran tersedia.</p>
          </div>
          <div v-else class="payment-summary">
            <div class="info-row">
              <div class="info-label">Total Tagihan:</div>
              <div class="info-value">{{ formatCurrency(invoice.total_amount) }}</div>
            </div>
                          <div class="info-row">
              <div class="info-label">Total Dibayar:</div>
              <div class="info-value">{{ formatCurrency(totalPaid) }}</div>
            </div>
            <div class="info-row">
              <div class="info-label">Sisa Tagihan:</div>
              <div class="info-value" :class="{'text-danger': remainingBalance > 0}">
                {{ formatCurrency(remainingBalance) }}
              </div>
            </div>
            <div class="info-row">
              <div class="info-label">Status Pembayaran:</div>
              <div class="info-value">
                <span :class="getPaymentStatusClass(paymentStatus)">
                  {{ paymentStatus }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Documents -->
      <div class="card" v-if="relatedReturns && relatedReturns.length > 0">
        <div class="card-header">
          <h3>Return Terkait</h3>
        </div>
        <div class="card-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nomor Return</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tindakan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="returnItem in relatedReturns" :key="returnItem.return_id">
                <td>{{ returnItem.return_number }}</td>
                <td>{{ formatDate(returnItem.return_date) }}</td>
                <td>{{ formatCurrency(returnItem.total_amount) }}</td>
                <td>
                  <span :class="getReturnStatusClass(returnItem.status)">
                    {{ returnItem.status }}
                  </span>
                </td>
                <td>
                  <button class="btn-icon" @click="viewReturn(returnItem)">
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

export default {
  name: 'SalesInvoiceDetail',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const invoice = ref({});
    const invoiceLines = ref([]);
    const receivables = ref([]);
    const relatedReturns = ref([]);
    const isLoading = ref(true);
    const error = ref(null);

    // Computed properties
    const invoiceId = computed(() => route.params.id);

    const customerName = computed(() =>
      invoice.value.customer ? invoice.value.customer.name : 'N/A'
    );

    const salesOrderNumber = computed(() =>
      invoice.value.salesOrder ? invoice.value.salesOrder.so_number : 'N/A'
    );

    const statusClass = computed(() => {
      switch (invoice.value.status) {
        case 'Draft': return 'status-draft';
        case 'Open': return 'status-open';
        case 'Paid': return 'status-paid';
        case 'Canceled': return 'status-canceled';
        case 'Closed': return 'status-closed';
        default: return '';
      }
    });

    const calculateSubtotal = computed(() => {
      if (!invoiceLines.value.length) return 0;
      return invoiceLines.value.reduce((sum, line) => sum + (line.subtotal || 0), 0);
    });

    const totalPaid = computed(() => {
      if (!receivables.value.length) return 0;
      return receivables.value.reduce((sum, rec) => sum + (rec.paid_amount || 0), 0);
    });

    const remainingBalance = computed(() => {
      if (!invoice.value.total_amount) return 0;
      return invoice.value.total_amount - totalPaid.value;
    });

    const paymentStatus = computed(() => {
      if (remainingBalance.value <= 0) return 'Lunas';
      if (totalPaid.value > 0) return 'Bayar Sebagian';

      // Check if overdue
      if (invoice.value.due_date) {
        const dueDate = new Date(invoice.value.due_date);
        const today = new Date();
        if (dueDate < today) return 'Terlambat';
      }

      return 'Belum Bayar';
    });

    const canEdit = computed(() =>
      !['Paid', 'Closed', 'Canceled'].includes(invoice.value.status)
    );

    // Methods
    const fetchInvoice = async () => {
      isLoading.value = true;
      error.value = null;

    try {
    // Change this line from:
    // const response = await axios.get(`/api/invoices/${invoiceId.value}`);
    // To:
    const response = await axios.get(`/api/sales/invoices/${invoiceId.value}`);

    invoice.value = response.data.data;
    invoiceLines.value = response.data.data.salesInvoiceLines || [];
    receivables.value = response.data.data.customerReceivables || [];
    relatedReturns.value = response.data.data.salesReturns || [];
  } catch (err) {
    console.error('Error fetching invoice:', err);
    error.value = 'Gagal memuat data invoice. Silakan coba lagi.';
  } finally {
    isLoading.value = false;
  }
};

    //   try {
    //     const response = await axios.get(`/api/invoices/${invoiceId.value}`);
    //     invoice.value = response.data.data;
    //     invoiceLines.value = response.data.data.salesInvoiceLines || [];
    //     receivables.value = response.data.data.customerReceivables || [];
    //     relatedReturns.value = response.data.data.salesReturns || [];
    //   } catch (err) {
    //     console.error('Error fetching invoice:', err);
    //     error.value = 'Gagal memuat data invoice. Silakan coba lagi.';
    //   } finally {
    //     isLoading.value = false;
    //   }
    // };

    const goBack = () => {
      router.go(-1);
    };

    const editInvoice = () => {
      router.push(`/sales/invoices/${invoiceId.value}/edit`);
    };

    const printInvoice = () => {
      router.push(`/sales/invoices/${invoiceId.value}/print`);
    };

    const viewPaymentInfo = () => {
      router.push(`/sales/invoices/${invoiceId.value}/payment-info`);
    };

    const viewSalesOrder = () => {
      if (invoice.value.so_id) {
        router.push(`/sales/orders/${invoice.value.so_id}`);
      }
    };

    const viewReturn = (returnItem) => {
      router.push(`/sales/returns/${returnItem.return_id}`);
    };

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    };

    const formatCurrency = (amount, currency) => {
      if (amount === undefined || amount === null) return 'N/A';
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency || invoice.value.currency_code || 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount);
    };

    const formatQuantity = (qty) => {
      if (qty === undefined || qty === null) return 'N/A';
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(qty);
    };

    const getPaymentStatusClass = (status) => {
      switch (status) {
        case 'Lunas': return 'status-badge status-paid';
        case 'Bayar Sebagian': return 'status-badge status-partial';
        case 'Terlambat': return 'status-badge status-overdue';
        case 'Belum Bayar': return 'status-badge status-unpaid';
        default: return 'status-badge';
      }
    };

    const getReturnStatusClass = (status) => {
      switch (status) {
        case 'Processing': return 'status-badge status-processing';
        case 'Completed': return 'status-badge status-completed';
        case 'Canceled': return 'status-badge status-canceled';
        default: return 'status-badge';
      }
    };

    onMounted(() => {
      fetchInvoice();
    });

    return {
      invoice,
      invoiceLines,
      receivables,
      relatedReturns,
      isLoading,
      error,
      invoiceId,
      customerName,
      salesOrderNumber,
      statusClass,
      calculateSubtotal,
      totalPaid,
      remainingBalance,
      paymentStatus,
      canEdit,
      fetchInvoice,
      goBack,
      editInvoice,
      printInvoice,
      viewPaymentInfo,
      viewSalesOrder,
      viewReturn,
      formatDate,
      formatCurrency,
      formatQuantity,
      getPaymentStatusClass,
      getReturnStatusClass
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
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
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

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
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

.btn-outline {
  background-color: transparent;
  border-color: var(--gray-300);
  color: var(--gray-700);
}

.btn-outline:hover {
  background-color: var(--gray-100);
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

.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--gray-800);
}

.card-header h3 {
  margin: 0;
  font-size: 1.125rem;
  color: var(--gray-800);
}

.card-body {
  padding: 1.5rem;
}

.invoice-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.invoice-status {
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  font-size: 0.875rem;
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

.status-partial {
  background-color: #fef3c7;
  color: #d97706;
}

.status-overdue {
  background-color: #fecaca;
  color: #b91c1c;
}

.status-unpaid {
  background-color: #e0f2fe;
  color: #0369a1;
}

.status-processing {
  background-color: #e0f2fe;
  color: #0369a1;
}

.status-completed {
  background-color: #dcfce7;
  color: #16a34a;
}

.row {
  display: flex;
  flex-wrap: wrap;
  margin: -0.5rem;
}

.col {
  flex: 1;
  padding: 0.5rem;
  min-width: 250px;
}

.info-group {
  margin-bottom: 1rem;
}

.info-label {
  font-size: 0.875rem;
  color: var(--gray-500);
  margin-bottom: 0.25rem;
}

.info-value {
  font-size: 1rem;
  color: var(--gray-800);
}

.info-row {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid var(--gray-100);
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
}

.info-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.total-value {
  font-weight: 600;
  font-size: 1.125rem;
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

.data-table tfoot {
  background-color: var(--gray-50);
}

.text-right {
  text-align: right;
}

.font-bold {
  font-weight: 600;
}

.text-danger {
  color: var(--danger-color);
}

.link {
  color: var(--primary-color);
  cursor: pointer;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

.loading-container, .error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  text-align: center;
}

.loading-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  color: var(--gray-500);
}

.loading-indicator i {
  font-size: 2rem;
}

.error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  color: var(--danger-color);
  margin-bottom: 1.5rem;
}

.error-message i {
  font-size: 2rem;
}

.no-data {
  padding: 2rem;
  text-align: center;
  color: var(--gray-500);
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

@media (max-width: 768px) {
  .row {
    flex-direction: column;
  }

  .col {
    min-width: 100%;
  }

  .invoice-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .data-table {
    font-size: 0.875rem;
  }

  .data-table th, .data-table td {
    padding: 0.5rem;
  }
}
</style>
