<!-- src/views/sales/SalesInvoiceForm.vue -->
<template>
  <div class="page-content">
    <div class="page-header">
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Kembali
      </button>
      <h1 class="page-title">{{ isEditMode ? 'Edit Invoice' : 'Buat Invoice Baru' }}</h1>
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
      <button class="btn btn-primary" @click="retry">Coba Lagi</button>
    </div>

    <div v-else class="invoice-form-container">
      <form @submit.prevent="saveInvoice" class="invoice-form">
        <!-- Invoice Header Card -->
        <div class="card">
          <div class="card-header">
            <h3>Informasi Invoice</h3>
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
                  :disabled="isEditMode"
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
                <label for="so_id">Sales Order <span class="required">*</span></label>
                <select
                  id="so_id"
                  v-model="form.so_id"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.so_id }"
                  required
                  :disabled="isEditMode"
                  @change="loadSalesOrder"
                >
                  <option value="" disabled selected>Pilih Sales Order</option>
                  <option v-for="order in salesOrders" :key="order.so_id" :value="order.so_id">
                    {{ order.so_number }} - {{ order.customer ? order.customer.name : 'N/A' }}
                  </option>
                </select>
                <div v-if="validationErrors.so_id" class="invalid-feedback">
                  {{ validationErrors.so_id[0] }}
                </div>
              </div>
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
                  <option value="Canceled">Canceled</option>
                  <option value="Closed">Closed</option>
                </select>
                <div v-if="validationErrors.status" class="invalid-feedback">
                  {{ validationErrors.status[0] }}
                </div>
              </div>
              <div class="form-group">
                <label for="currency_code">Mata Uang</label>
                <select
                  id="currency_code"
                  v-model="form.currency_code"
                  class="form-control"
                  :disabled="isEditMode"
                >
                  <option value="IDR">IDR - Indonesian Rupiah</option>
                  <option value="USD">USD - US Dollar</option>
                  <option value="EUR">EUR - Euro</option>
                  <option value="SGD">SGD - Singapore Dollar</option>
                </select>
              </div>
            </div>
            <div class="form-row" v-if="currentSalesOrder">
              <div class="form-group info-box">
                <h4>Detail Sales Order</h4>
                <div class="info-item">
                  <div class="info-label">Pelanggan:</div>
                  <div class="info-value">{{ currentSalesOrder.customer ? currentSalesOrder.customer.name : 'N/A' }}</div>
                </div>
                <div class="info-item">
                  <div class="info-label">Tanggal Order:</div>
                  <div class="info-value">{{ formatDate(currentSalesOrder.so_date) }}</div>
                </div>
                <div class="info-item">
                  <div class="info-label">Total Order:</div>
                  <div class="info-value">{{ formatCurrency(currentSalesOrder.total_amount, currentSalesOrder.currency_code) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoice Lines Card -->
        <div class="card">
          <div class="card-header">
            <h3>Detail Item</h3>
          </div>
          <div class="card-body">
            <div v-if="!availableItems.length" class="no-data">
              <p>Silahkan pilih Sales Order terlebih dahulu.</p>
            </div>
            <table v-else class="data-table">
              <thead>
                <tr>
                  <th style="width: 50px">Pilih</th>
                  <th>Kode Item</th>
                  <th>Deskripsi</th>
                  <th>Qty Order</th>
                  <th>Qty Invoice</th>
                  <th>Harga Satuan</th>
                  <th>Diskon</th>
                  <th>Subtotal</th>
                  <th>Pajak</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in availableItems" :key="item.line_id">
                  <td>
                    <input
                      type="checkbox"
                      :id="`item_${item.line_id}`"
                      v-model="item.selected"
                      @change="updateTotals"
                    />
                  </td>
                  <td>{{ item.item ? item.item.item_code : 'N/A' }}</td>
                  <td>{{ item.item ? item.item.name : 'N/A' }}</td>
                  <td>{{ formatQuantity(item.quantity) }}</td>
                  <td>
                    <input
                      type="number"
                      v-model.number="item.invoice_quantity"
                      class="form-control form-control-sm"
                      :disabled="!item.selected"
                      :max="item.quantity"
                      :min="0.01"
                      step="0.01"
                      @input="updateLineTotals(item)"
                    />
                  </td>
                  <td>{{ formatCurrency(item.unit_price) }}</td>
                  <td>{{ formatCurrency(item.discount_per_unit * item.invoice_quantity) }}</td>
                  <td>{{ formatCurrency(item.subtotal) }}</td>
                  <td>{{ formatCurrency(item.tax) }}</td>
                  <td>{{ formatCurrency(item.total) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="7" class="text-right"><strong>Total:</strong></td>
                  <td>{{ formatCurrency(totalSubtotal) }}</td>
                  <td>{{ formatCurrency(totalTax) }}</td>
                  <td>{{ formatCurrency(totalAmount) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="goBack">Batal</button>
          <button type="submit" class="btn btn-primary" :disabled="isSaving || !isFormValid">
            <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
            {{ isEditMode ? 'Update Invoice' : 'Buat Invoice' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

export default {
  name: 'SalesInvoiceForm',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const isLoading = ref(true);
    const isSaving = ref(false);
    const error = ref(null);
    const validationErrors = ref({});
    const salesOrders = ref([]);
    const currentSalesOrder = ref(null);
    const availableItems = ref([]);

    // Form state
    const form = ref({
      invoice_number: '',
      invoice_date: new Date().toISOString().split('T')[0],
      due_date: '',
      so_id: '',
      status: 'Draft',
      currency_code: 'IDR',
      lines: []
    });

    // Computed properties
    const isEditMode = computed(() => !!route.params.id);

    const invoiceId = computed(() => route.params.id);

    const totalSubtotal = computed(() => {
      return availableItems.value
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.subtotal, 0);
    });

    const totalTax = computed(() => {
      return availableItems.value
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.tax, 0);
    });

    const totalAmount = computed(() => {
      return availableItems.value
        .filter(item => item.selected)
        .reduce((sum, item) => sum + item.total, 0);
    });

    const isFormValid = computed(() => {
      return form.value.invoice_number &&
             form.value.invoice_date &&
             form.value.due_date &&
             form.value.so_id &&
             form.value.status &&
             availableItems.value.some(item => item.selected);
    });

    // Methods
    const loadSalesOrders = async () => {
      try {
        const response = await axios.get('/api/orders');
        salesOrders.value = response.data.data.filter(order =>
          ['Confirmed', 'Delivered'].includes(order.status)
        );
      } catch (err) {
        console.error('Error loading sales orders:', err);
        error.value = 'Gagal memuat daftar sales order. Silakan coba lagi.';
      }
    };

    const loadSalesOrder = async () => {
      if (!form.value.so_id) {
        currentSalesOrder.value = null;
        availableItems.value = [];
        return;
      }

      try {
        const response = await axios.get(`/api/orders/${form.value.so_id}`);
        currentSalesOrder.value = response.data.data;

        // Set default due date based on payment terms if available
        if (currentSalesOrder.value.payment_terms) {
          const terms = parseInt(currentSalesOrder.value.payment_terms);
          if (!isNaN(terms)) {
            const dueDate = new Date(form.value.invoice_date);
            dueDate.setDate(dueDate.getDate() + terms);
            form.value.due_date = dueDate.toISOString().split('T')[0];
          }
        }

        // Setup invoice items from order lines
        availableItems.value = currentSalesOrder.value.salesOrderLines.map(line => {
          // Calculate per unit values for easier calculation when quantity changes
          const discount_per_unit = line.discount / line.quantity;
          const tax_per_unit = line.tax / line.quantity;

          return {
            ...line,
            selected: isEditMode.value ? false : true, // By default select all items for new invoice
            invoice_quantity: line.quantity, // Default to full quantity
            discount_per_unit,
            tax_per_unit,
            subtotal: line.unit_price * line.quantity,
            tax: line.tax,
            total: (line.unit_price * line.quantity) - line.discount + line.tax
          };
        });

        // Set currency from order
        if (currentSalesOrder.value.currency_code) {
          form.value.currency_code = currentSalesOrder.value.currency_code;
        }

        // Update totals
        updateTotals();

      } catch (err) {
        console.error('Error loading sales order details:', err);
        error.value = 'Gagal memuat detail sales order. Silakan coba lagi.';
      }
    };

    const updateLineTotals = (item) => {
      if (!item.selected || item.invoice_quantity <= 0) {
        item.subtotal = 0;
        item.tax = 0;
        item.total = 0;
        return;
      }

      // Recalculate totals based on invoice quantity
      item.subtotal = item.unit_price * item.invoice_quantity;
      item.discount = item.discount_per_unit * item.invoice_quantity;
      item.tax = item.tax_per_unit * item.invoice_quantity;
      item.total = item.subtotal - item.discount + item.tax;

      updateTotals();
    };

    const updateTotals = () => {
      // This will trigger the computed properties to recalculate
      availableItems.value.forEach(item => {
        if (!item.selected) {
          item.subtotal = 0;
          item.tax = 0;
          item.total = 0;
        } else {
          updateLineTotals(item);
        }
      });
    };

    const loadInvoice = async () => {
      try {
        const response = await axios.get(`/api/invoices/${invoiceId.value}`);
        const invoice = response.data.data;

        // Fill form with invoice data
        form.value = {
          invoice_number: invoice.invoice_number,
          invoice_date: new Date(invoice.invoice_date).toISOString().split('T')[0],
          due_date: new Date(invoice.due_date).toISOString().split('T')[0],
          so_id: invoice.so_id,
          status: invoice.status,
          currency_code: invoice.currency_code || 'IDR'
        };

        // Need to load the SO to get the available items
        await loadSalesOrder();

        // Match invoice lines with order lines and set quantities
        if (invoice.salesInvoiceLines && invoice.salesInvoiceLines.length > 0) {
          invoice.salesInvoiceLines.forEach(invoiceLine => {
            const matchingItem = availableItems.value.find(
              item => item.line_id === invoiceLine.so_line_id
            );

            if (matchingItem) {
              matchingItem.selected = true;
              matchingItem.invoice_quantity = invoiceLine.quantity;
              updateLineTotals(matchingItem);
            }
          });
        }

      } catch (err) {
        console.error('Error loading invoice:', err);
        error.value = 'Gagal memuat data invoice. Silakan coba lagi.';
      }
    };

    const prepareFormData = () => {
      const selectedLines = availableItems.value
        .filter(item => item.selected)
        .map(item => ({
          so_line_id: item.line_id,
          quantity: item.invoice_quantity
        }));

      return {
        ...form.value,
        lines: selectedLines
      };
    };

    const saveInvoice = async () => {
      validationErrors.value = {};
      isSaving.value = true;

      try {
        const formData = prepareFormData();

        let response;
        if (isEditMode.value) {
          response = await axios.put(`/api/invoices/${invoiceId.value}`, formData);
        } else {
          response = await axios.post('/api/invoices', formData);
        }

        router.push(`/sales/invoices/${response.data.data.invoice_id}`);
      } catch (err) {
        console.error('Error saving invoice:', err);

        if (err.response && err.response.status === 422) {
          validationErrors.value = err.response.data.errors;
        } else {
          error.value = 'Gagal menyimpan invoice. Silakan coba lagi.';
        }
      } finally {
        isSaving.value = false;
      }
    };

    const goBack = () => {
      router.go(-1);
    };

    const retry = () => {
      error.value = null;
      if (isEditMode.value) {
        loadInvoice();
      } else {
        loadSalesOrders();
      }
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
        currency: currency || form.value.currency_code || 'IDR',
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

    // Watch for changes to invoice date to update due date
    watch(() => form.value.invoice_date, (newDate) => {
      if (!newDate || form.value.due_date) return;

      // Default due date to 30 days after invoice date
      const dueDate = new Date(newDate);
      dueDate.setDate(dueDate.getDate() + 30);
      form.value.due_date = dueDate.toISOString().split('T')[0];
    });

    // Initialize
    onMounted(async () => {
      try {
        if (isEditMode.value) {
          await loadInvoice();
        } else {
          await loadSalesOrders();
        }
      } catch (err) {
        console.error('Error during initialization:', err);
        error.value = 'Terjadi kesalahan saat memuat halaman. Silakan coba lagi.';
      } finally {
        isLoading.value = false;
      }
    });

    return {
      form,
      isLoading,
      isSaving,
      error,
      validationErrors,
      salesOrders,
      currentSalesOrder,
      availableItems,
      isEditMode,
      invoiceId,
      totalSubtotal,
      totalTax,
      totalAmount,
      isFormValid,
      loadSalesOrders,
      loadSalesOrder,
      updateLineTotals,
      updateTotals,
      saveInvoice,
      goBack,
      retry,
      formatDate,
      formatCurrency,
      formatQuantity
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
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
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
}

.card-header h3 {
  margin: 0;
  font-size: 1.125rem;
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

.form-control-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
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

.info-box {
  background-color: var(--gray-50);
  border-radius: 0.375rem;
  padding: 1rem;
  box-shadow: inset 0 0 0 1px var(--gray-200);
}

.info-box h4 {
  margin-top: 0;
  margin-bottom: 0.75rem;
  font-size: 1rem;
  color: var(--gray-700);
}

.info-item {
  display: flex;
  margin-bottom: 0.5rem;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-label {
  font-size: 0.875rem;
  color: var(--gray-600);
  width: 40%;
}

.info-value {
  font-size: 0.875rem;
  color: var(--gray-800);
  font-weight: 500;
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

.no-data {
  padding: 2rem;
  text-align: center;
  color: var(--gray-500);
}

@media (max-width: 768px) {
  .form-group {
    min-width: 100%;
  }

  .data-table {
    font-size: 0.875rem;
  }

  .data-table th, .data-table td {
    padding: 0.5rem;
  }
}
</style>
