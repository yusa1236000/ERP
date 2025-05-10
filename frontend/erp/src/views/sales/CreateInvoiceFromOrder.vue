<!-- src/views/sales/CreateInvoiceFromOrder.vue -->

<!-- =================== TEMPLATE SECTION =================== -->
<template>
  <div class="page-content">
    <div class="page-header">
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Kembali
      </button>
      <h1 class="page-title">Buat Invoice dari Sales Order</h1>
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
      <button class="btn btn-primary" @click="loadSalesOrders">Coba Lagi</button>
    </div>

    <div v-else class="create-from-order-container">
      <form @submit.prevent="createInvoice" class="create-from-order-form">
        <!-- Order Selection Card -->
        <div class="card">
          <div class="card-header">
            <h3>Pilih Sales Order</h3>
          </div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-12">
                <label for="so_id">Sales Order <span class="required">*</span></label>
                <select
                  id="so_id"
                  v-model="form.so_id"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.so_id }"
                  required
                  @change="loadSalesOrderDetails"
                >
                  <option value="" disabled selected>Pilih Sales Order</option>
                  <option v-for="order in salesOrders" :key="order.so_id" :value="order.so_id">
                    {{ order.so_number }} - {{ order.customer ? order.customer.name : 'N/A' }} ({{ formatCurrency(order.total_amount, order.currency_code) }})
                  </option>
                </select>
                <div v-if="validationErrors.so_id" class="invalid-feedback">
                  {{ validationErrors.so_id[0] }}
                </div>
              </div>
            </div>

            <div v-if="selectedOrder" class="order-details">
              <div class="detail-section">
                <h4>Detail Sales Order</h4>
                <div class="detail-grid">
                  <div class="detail-item">
                    <div class="detail-label">Nomor SO:</div>
                    <div class="detail-value">{{ selectedOrder.so_number }}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Tanggal:</div>
                    <div class="detail-value">{{ formatDate(selectedOrder.so_date) }}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Pelanggan:</div>
                    <div class="detail-value">{{ selectedOrder.customer ? selectedOrder.customer.name : 'N/A' }}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                      <span :class="getStatusClass(selectedOrder.status)">{{ selectedOrder.status }}</span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Total:</div>
                    <div class="detail-value">{{ formatCurrency(selectedOrder.total_amount, selectedOrder.currency_code) }}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Mata Uang:</div>
                    <div class="detail-value">{{ selectedOrder.currency_code || 'IDR' }}</div>
                  </div>
                </div>
              </div>

              <div class="detail-section">
                <h4>Item Order</h4>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Kode Item</th>
                      <th>Deskripsi</th>
                      <th class="text-right">Qty</th>
                      <th class="text-right">Harga Satuan</th>
                      <th class="text-right">Subtotal</th>
                      <th class="text-right">Pajak</th>
                      <th class="text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="line in selectedOrder.salesOrderLines" :key="line.line_id">
                      <td>{{ line.item ? line.item.item_code : 'N/A' }}</td>
                      <td>{{ line.item ? line.item.name : 'N/A' }}</td>
                      <td class="text-right">{{ formatQuantity(line.quantity) }}</td>
                      <td class="text-right">{{ formatCurrency(line.unit_price, selectedOrder.currency_code) }}</td>
                      <td class="text-right">{{ formatCurrency(line.subtotal, selectedOrder.currency_code) }}</td>
                      <td class="text-right">{{ formatCurrency(line.tax, selectedOrder.currency_code) }}</td>
                      <td class="text-right">{{ formatCurrency(line.total, selectedOrder.currency_code) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="4" class="text-right"><strong>Total:</strong></td>
                      <td class="text-right">{{ formatCurrency(calculateSubtotal, selectedOrder.currency_code) }}</td>
                      <td class="text-right">{{ formatCurrency(selectedOrder.tax_amount, selectedOrder.currency_code) }}</td>
                      <td class="text-right">{{ formatCurrency(selectedOrder.total_amount, selectedOrder.currency_code) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoice Details Card -->
        <div v-if="selectedOrder" class="card">
          <div class="card-header">
            <h3>Detail Invoice</h3>
          </div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label for="invoice_number">Nomor Invoice <span class="required">*</span></label>
                <input
                  type="text"
                  id="invoice_number"
                  v-model="form.invoice_number"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.invoice_number }"
                  required
                />
                <div v-if="validationErrors.invoice_number" class="invalid-feedback">
                  {{ validationErrors.invoice_number[0] }}
                </div>
              </div>
              <div class="form-group">
                <label for="invoice_date">Tanggal Invoice <span class="required">*</span></label>
                <input
                  type="date"
                  id="invoice_date"
                  v-model="form.invoice_date"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.invoice_date }"
                  required
                />
                <div v-if="validationErrors.invoice_date" class="invalid-feedback">
                  {{ validationErrors.invoice_date[0] }}
                </div>
              </div>
              <div class="form-group">
                <label for="due_date">Tanggal Jatuh Tempo <span class="required">*</span></label>
                <input
                  type="date"
                  id="due_date"
                  v-model="form.due_date"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.due_date }"
                  required
                />
                <div v-if="validationErrors.due_date" class="invalid-feedback">
                  {{ validationErrors.due_date[0] }}
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="status">Status <span class="required">*</span></label>
                <select
                  id="status"
                  v-model="form.status"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.status }"
                  required
                >
                  <option value="Draft">Draft</option>
                  <option value="Open">Open</option>
                  <option value="Paid">Paid</option>
                </select>
                <div v-if="validationErrors.status" class="invalid-feedback">
                  {{ validationErrors.status[0] }}
                </div>
              </div>
              <div class="form-group">
                <label for="currency_code">Mata Uang</label>
                <input
                  type="text"
                  id="currency_code"
                  v-model="form.currency_code"
                  class="form-control"
                  disabled
                />
              </div>
              <div class="form-group">
                <label>&nbsp;</label>
                <div class="form-note">
                  <i class="fas fa-info-circle"></i>
                  Invoice akan dibuat dengan semua item dari Sales Order.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="goBack">Batal</button>
          <button type="submit" class="btn btn-primary" :disabled="isSaving || !isFormValid">
            <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
            Buat Invoice
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<!-- =================== SCRIPT SECTION =================== -->
<script>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';

export default {
  name: 'CreateInvoiceFromOrder',
  setup() {
    const router = useRouter();
    const isLoading = ref(true);
    const isSaving = ref(false);
    const error = ref(null);
    const validationErrors = ref({});
    const salesOrders = ref([]);
    const selectedOrder = ref(null);

    // Form state
    const form = ref({
      so_id: '',
      invoice_number: generateInvoiceNumber(),
      invoice_date: new Date().toISOString().split('T')[0],
      due_date: '',
      status: 'Draft',
      currency_code: 'IDR'
    });

    // Computed properties
    const isFormValid = computed(() => {
      return form.value.so_id &&
             form.value.invoice_number &&
             form.value.invoice_date &&
             form.value.due_date &&
             form.value.status;
    });

    const calculateSubtotal = computed(() => {
      if (!selectedOrder.value || !selectedOrder.value.salesOrderLines) return 0;

      return selectedOrder.value.salesOrderLines.reduce(
        (sum, line) => sum + (line.subtotal || 0), 0
      );
    });

    // Methods
    function generateInvoiceNumber() {
      const now = new Date();
      const year = now.getFullYear().toString();
      const month = (now.getMonth() + 1).toString().padStart(2, '0');
      const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');

      return `INV/${year}${month}/${randomNum}`;
    }

    const loadSalesOrders = async () => {
      isLoading.value = true;
      error.value = null;

      try {
        const response = await axios.get('/api/orders');
        // Filter to only get orders that are eligible for invoicing
        salesOrders.value = response.data.data.filter(order =>
          ['Confirmed', 'Delivered'].includes(order.status)
        );
      } catch (err) {
        console.error('Error loading sales orders:', err);
        error.value = 'Gagal memuat daftar sales order. Silakan coba lagi.';
      } finally {
        isLoading.value = false;
      }
    };

    const loadSalesOrderDetails = async () => {
      if (!form.value.so_id) {
        selectedOrder.value = null;
        return;
      }

      isLoading.value = true;
      error.value = null;

      try {
        const response = await axios.get(`/api/orders/${form.value.so_id}`);
        selectedOrder.value = response.data.data;

        // Update currency code
        form.value.currency_code = selectedOrder.value.currency_code || 'IDR';

        // Set default due date based on payment terms if available
        if (selectedOrder.value.payment_terms) {
          const terms = parseInt(selectedOrder.value.payment_terms);
          if (!isNaN(terms)) {
            const dueDate = new Date(form.value.invoice_date);
            dueDate.setDate(dueDate.getDate() + terms);
            form.value.due_date = dueDate.toISOString().split('T')[0];
          }
        } else {
          // Default to 30 days
          const dueDate = new Date(form.value.invoice_date);
          dueDate.setDate(dueDate.getDate() + 30);
          form.value.due_date = dueDate.toISOString().split('T')[0];
        }
      } catch (err) {
        console.error('Error loading sales order details:', err);
        error.value = 'Gagal memuat detail sales order. Silakan coba lagi.';
        selectedOrder.value = null;
      } finally {
        isLoading.value = false;
      }
    };

    const createInvoice = async () => {
      validationErrors.value = {};
      isSaving.value = true;

      try {
        const response = await axios.post('/api/sales/invoices/from-order', form.value);

        // Navigate to the newly created invoice
        router.push(`/sales/invoices/${response.data.data.invoice_id}`);
      } catch (err) {
        console.error('Error creating invoice:', err);

        if (err.response && err.response.status === 422) {
          validationErrors.value = err.response.data.errors;
        } else {
          error.value = 'Gagal membuat invoice. Silakan coba lagi.';
        }
      } finally {
        isSaving.value = false;
      }
    };

    const goBack = () => {
      router.go(-1);
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

    const formatCurrency = (amount, currency = 'IDR') => {
      if (amount === undefined || amount === null) return 'N/A';
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
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

    const getStatusClass = (status) => {
      switch (status) {
        case 'Draft': return 'status-badge status-draft';
        case 'Confirmed': return 'status-badge status-confirmed';
        case 'Delivered': return 'status-badge status-delivered';
        case 'Invoiced': return 'status-badge status-invoiced';
        case 'Canceled': return 'status-badge status-canceled';
        case 'Closed': return 'status-badge status-closed';
        default: return 'status-badge';
      }
    };

    // Watch for invoice date changes
    watch(() => form.value.invoice_date, (newDate, oldDate) => {
      if (newDate === oldDate || !newDate) return;

      // If payment terms available in selected order, use that
      if (selectedOrder.value && selectedOrder.value.payment_terms) {
        const terms = parseInt(selectedOrder.value.payment_terms);
        if (!isNaN(terms)) {
          const dueDate = new Date(newDate);
          dueDate.setDate(dueDate.getDate() + terms);
          form.value.due_date = dueDate.toISOString().split('T')[0];
        }
      } else {
        // Default to 30 days
        const dueDate = new Date(newDate);
        dueDate.setDate(dueDate.getDate() + 30);
        form.value.due_date = dueDate.toISOString().split('T')[0];
      }
    });

    // Initialize
    onMounted(() => {
      loadSalesOrders();
    });

    return {
      isLoading,
      isSaving,
      error,
      validationErrors,
      salesOrders,
      selectedOrder,
      form,
      isFormValid,
      calculateSubtotal,
      loadSalesOrders,
      loadSalesOrderDetails,
      createInvoice,
      goBack,
      formatDate,
      formatCurrency,
      formatQuantity,
      getStatusClass
    };
  }
};
</script>

<!-- =================== STYLE SECTION =================== -->
<style scoped>
.page-content {
  padding: 1rem;
}

.page-header {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
  color: var(--gray-800);
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

.btn-primary:hover:not(:disabled) {
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

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
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
}

.card-header h3 {
  margin: 0;
  font-size: 1.125rem;
  color: var(--gray-800);
}

.card-body {
  padding: 1.5rem;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  margin: -0.5rem;
  margin-bottom: 1rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  flex: 1;
  min-width: 250px;
  padding: 0.5rem;
}

.col-12 {
  flex: 0 0 100%;
  max-width: 100%;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: var(--gray-800);
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  border-color: #90cdf4;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(66, 153, 225, 0.25);
}

.form-control:disabled {
  background-color: var(--gray-100);
  opacity: 1;
}

.form-control.is-invalid {
  border-color: var(--danger-color);
}

.form-note {
  padding: 0.5rem;
  background-color: var(--gray-50);
  border-radius: 0.25rem;
  color: var(--gray-600);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 80%;
  color: var(--danger-color);
}

label {
  display: inline-block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.required {
  color: var(--danger-color);
}

.order-details {
  margin-top: 1.5rem;
}

.detail-section {
  margin-bottom: 1.5rem;
  padding: 1.25rem;
  background-color: var(--gray-50);
  border-radius: 0.375rem;
  border: 1px solid var(--gray-200);
}

.detail-section h4 {
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1rem;
  color: var(--gray-700);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-bottom: 0.25rem;
}

.detail-value {
  font-size: 0.875rem;
  color: var(--gray-800);
  font-weight: 500;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.data-table th, .data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--gray-200);
}

.data-table th {
  font-weight: 500;
  color: var(--gray-600);
  background-color: var(--gray-100);
}

.data-table tbody tr:hover {
  background-color: var(--gray-50);
}

.data-table tfoot {
  background-color: var(--gray-100);
}

.data-table tfoot td {
  font-weight: 500;
}

.text-right {
  text-align: right;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
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

.status-confirmed {
  background-color: #e0f2fe;
  color: #0369a1;
}

.status-delivered {
  background-color: #dcfce7;
  color: #16a34a;
}

.status-invoiced {
  background-color: #fef3c7;
  color: #d97706;
}

.status-canceled {
  background-color: #fee2e2;
  color: #dc2626;
}

.status-closed {
  background-color: #f3f4f6;
  color: #1f2937;
}

@media (max-width: 768px) {
  .form-group {
    min-width: 100%;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .data-table {
    font-size: 0.875rem;
  }

  .data-table th, .data-table td {
    padding: 0.5rem;
  }
}
</style>
