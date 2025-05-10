<!-- src/views/sales/InvoicePaymentInfo.vue -->

<!-- =================== TEMPLATE SECTION =================== -->
<template>
  <div class="page-content">
    <div class="page-header">
      <button class="btn btn-secondary" @click="goBack">
        <i class="fas fa-arrow-left"></i> Kembali
      </button>
      <h1 class="page-title">Informasi Pembayaran Invoice</h1>
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
      <button class="btn btn-primary" @click="fetchPaymentInfo">Coba Lagi</button>
    </div>

    <div v-else class="payment-info-container">
      <!-- Invoice Summary Card -->
      <div class="card">
        <div class="card-header">
          <h3>Ringkasan Invoice</h3>
          <button class="btn btn-sm btn-outline" @click="viewInvoice">
            <i class="fas fa-eye"></i> Lihat Invoice
          </button>
        </div>
        <div class="card-body">
          <div class="summary-grid">
            <div class="summary-item">
              <div class="summary-label">Nomor Invoice:</div>
              <div class="summary-value">{{ invoice.invoice_number || 'N/A' }}</div>
            </div>
            <div class="summary-item">
              <div class="summary-label">Tanggal Invoice:</div>
              <div class="summary-value">{{ formatDate(invoice.invoice_date) }}</div>
            </div>
            <div class="summary-item">
              <div class="summary-label">Jatuh Tempo:</div>
              <div class="summary-value" :class="{ 'text-danger': isOverdue }">
                {{ formatDate(invoice.due_date) }}
                <span v-if="isOverdue" class="overdue-tag">Terlambat</span>
              </div>
            </div>
            <div class="summary-item">
              <div class="summary-label">Pelanggan:</div>
              <div class="summary-value">{{ customerName }}</div>
            </div>
            <div class="summary-item">
              <div class="summary-label">Total Invoice:</div>
              <div class="summary-value">{{ formatCurrency(invoice.total_amount) }}</div>
            </div>
            <div class="summary-item">
              <div class="summary-label">Status:</div>
              <div class="summary-value">
                <span :class="getStatusClass(invoice.status)">
                  {{ invoice.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Status Card -->
      <div class="card">
        <div class="card-header">
          <h3>Status Pembayaran</h3>
          <button
            v-if="canRecordPayment"
            class="btn btn-primary"
            @click="showRecordPaymentModal = true"
          >
            <i class="fas fa-plus-circle"></i> Catat Pembayaran
          </button>
        </div>
        <div class="card-body">
          <div class="payment-status">
            <div class="status-item">
              <div class="status-label">Status Pembayaran:</div>
              <div class="status-value">
                <span :class="getPaymentStatusClass(paymentStatus)">
                  {{ paymentStatus }}
                </span>
              </div>
            </div>
            <div class="status-item">
              <div class="status-label">Total Invoice:</div>
              <div class="status-value">{{ formatCurrency(invoice.total_amount) }}</div>
            </div>
            <div class="status-item">
              <div class="status-label">Total Dibayar:</div>
              <div class="status-value">{{ formatCurrency(totalPaid) }}</div>
            </div>
            <div class="status-item">
              <div class="status-label">Sisa Pembayaran:</div>
              <div class="status-value" :class="{'text-danger': remainingBalance > 0}">
                {{ formatCurrency(remainingBalance) }}
              </div>
            </div>
          </div>

          <div v-if="paymentStatus === 'Terlambat'" class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span>
              Invoice ini telah melewati tanggal jatuh tempo. Segera lakukan pembayaran untuk menghindari denda keterlambatan.
            </span>
          </div>

          <div class="progress-container">
            <div class="progress-label">
              <span>Progres Pembayaran:</span>
              <span>{{ paymentProgressPercent }}%</span>
            </div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                :style="`width: ${paymentProgressPercent}%`"
                :class="getProgressColorClass"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment History Card -->
      <div class="card">
        <div class="card-header">
          <h3>Riwayat Pembayaran</h3>
        </div>
        <div class="card-body">
          <div v-if="!payments || payments.length === 0" class="no-data">
            <p>Belum ada riwayat pembayaran.</p>
          </div>
          <table v-else class="data-table">
            <thead>
              <tr>
                <th>No. Referensi</th>
                <th>Tanggal</th>
                <th>Metode Pembayaran</th>
                <th class="text-right">Jumlah</th>
                <th>Catatan</th>
                <th>Diproses Oleh</th>
                <th v-if="canVoidPayment">Tindakan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.payment_id">
                <td>{{ payment.reference_number }}</td>
                <td>{{ formatDate(payment.payment_date) }}</td>
                <td>{{ payment.payment_method }}</td>
                <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
                <td>{{ payment.notes || '-' }}</td>
                <td>{{ payment.processed_by || 'Sistem' }}</td>
                <td v-if="canVoidPayment">
                  <button
                    v-if="!payment.is_voided"
                    class="btn-icon btn-danger"
                    @click="confirmVoidPayment(payment)"
                    title="Batalkan Pembayaran"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                  <span v-else class="voided-tag">Dibatalkan</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Invoice Print and Email Actions -->
      <div class="card">
        <div class="card-header">
          <h3>Tindakan</h3>
        </div>
        <div class="card-body">
          <div class="action-buttons">
            <button class="btn btn-outline" @click="printReceipt">
              <i class="fas fa-print"></i> Cetak Tanda Terima
            </button>
            <button class="btn btn-outline" @click="sendPaymentReminder">
              <i class="fas fa-envelope"></i> Kirim Pengingat Pembayaran
            </button>
            <button class="btn btn-outline" @click="downloadStatement">
              <i class="fas fa-file-download"></i> Unduh Laporan Pembayaran
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Record Payment Modal -->
    <div v-if="showRecordPaymentModal" class="modal">
      <div class="modal-backdrop" @click="showRecordPaymentModal = false"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>Catat Pembayaran Baru</h2>
          <button class="close-btn" @click="showRecordPaymentModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="savePayment">
            <div class="form-group">
              <label for="payment_date">Tanggal Pembayaran <span class="required">*</span></label>
              <input
                type="date"
                id="payment_date"
                v-model="paymentForm.payment_date"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.payment_date }"
                required
              />
              <div v-if="validationErrors.payment_date" class="invalid-feedback">
                {{ validationErrors.payment_date[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="payment_method">Metode Pembayaran <span class="required">*</span></label>
              <select
                id="payment_method"
                v-model="paymentForm.payment_method"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.payment_method }"
                required
              >
                <option value="" disabled selected>Pilih Metode Pembayaran</option>
                <option value="Cash">Tunai</option>
                <option value="Bank Transfer">Transfer Bank</option>
                <option value="Credit Card">Kartu Kredit</option>
                <option value="Debit Card">Kartu Debit</option>
                <option value="Check">Cek</option>
                <option value="Other">Lainnya</option>
              </select>
              <div v-if="validationErrors.payment_method" class="invalid-feedback">
                {{ validationErrors.payment_method[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="reference_number">Nomor Referensi</label>
              <input
                type="text"
                id="reference_number"
                v-model="paymentForm.reference_number"
                placeholder="Nomor referensi pembayaran"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.reference_number }"
              />
              <div v-if="validationErrors.reference_number" class="invalid-feedback">
                {{ validationErrors.reference_number[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="amount">Jumlah Pembayaran <span class="required">*</span></label>
              <input
                type="number"
                id="amount"
                v-model.number="paymentForm.amount"
                placeholder="Masukkan jumlah pembayaran"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.amount }"
                :max="remainingBalance"
                :min="0.01"
                step="0.01"
                required
              />
              <div class="form-note">
                <i class="fas fa-info-circle"></i>
                Sisa pembayaran: {{ formatCurrency(remainingBalance) }}
              </div>
              <div v-if="validationErrors.amount" class="invalid-feedback">
                {{ validationErrors.amount[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="notes">Catatan</label>
              <textarea
                id="notes"
                v-model="paymentForm.notes"
                placeholder="Tambahkan catatan pembayaran (opsional)"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.notes }"
                rows="3"
              ></textarea>
              <div v-if="validationErrors.notes" class="invalid-feedback">
                {{ validationErrors.notes[0] }}
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showRecordPaymentModal = false">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting || !isPaymentFormValid">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                Simpan Pembayaran
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Void Payment Confirmation Modal -->
    <confirmation-modal
      v-if="showVoidConfirmation"
      title="Konfirmasi Pembatalan Pembayaran"
      :message="`Apakah Anda yakin ingin membatalkan pembayaran dengan referensi <strong>${paymentToVoid?.reference_number || '-'}</strong> sebesar <strong>${formatCurrency(paymentToVoid?.amount)}</strong>?<br>Tindakan ini tidak dapat dibatalkan.`"
      confirm-button-text="Batalkan Pembayaran"
      confirm-button-class="btn btn-danger"
      @confirm="voidPayment"
      @close="cancelVoidPayment"
    />
  </div>
</template>

<!-- =================== SCRIPT SECTION =================== -->
<script>
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

export default {
  name: 'InvoicePaymentInfo',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const invoice = ref({});
    const receivable = ref({});
    const payments = ref([]);
    const isLoading = ref(true);
    const error = ref(null);

    // Modal states
    const showRecordPaymentModal = ref(false);
    const showVoidConfirmation = ref(false);
    const paymentToVoid = ref(null);
    const isSubmitting = ref(false);
    const validationErrors = ref({});

    // Payment form
    const paymentForm = ref({
      payment_date: new Date().toISOString().split('T')[0],
      payment_method: '',
      reference_number: '',
      amount: 0,
      notes: ''
    });

    // Computed properties
    const invoiceId = computed(() => route.params.id);

    const customerName = computed(() =>
      invoice.value.customer ? invoice.value.customer.name : 'N/A'
    );

    const totalPaid = computed(() => {
      if (!receivable.value) return 0;
      return receivable.value.paid_amount || 0;
    });

    const remainingBalance = computed(() => {
      if (!receivable.value || !invoice.value.total_amount) return 0;
      return invoice.value.total_amount - totalPaid.value;
    });

    const paymentProgressPercent = computed(() => {
      if (!invoice.value.total_amount) return 0;
      const percent = (totalPaid.value / invoice.value.total_amount) * 100;
      return Math.min(100, Math.round(percent));
    });

    const getProgressColorClass = computed(() => {
      if (paymentProgressPercent.value >= 100) return 'progress-success';
      if (paymentProgressPercent.value >= 50) return 'progress-warning';
      return 'progress-danger';
    });

    const isOverdue = computed(() => {
      if (!invoice.value.due_date) return false;

      const dueDate = new Date(invoice.value.due_date);
      const today = new Date();

      return dueDate < today && remainingBalance.value > 0;
    });

    const paymentStatus = computed(() => {
      if (remainingBalance.value <= 0) return 'Lunas';
      if (totalPaid.value > 0) return 'Bayar Sebagian';
      return isOverdue.value ? 'Terlambat' : 'Belum Bayar';
    });

    const canRecordPayment = computed(() =>
      remainingBalance.value > 0 && !['Closed', 'Canceled'].includes(invoice.value.status)
    );

    const canVoidPayment = computed(() =>
      ['Admin', 'Finance Manager'].includes(getCurrentUserRole())
    );

    const isPaymentFormValid = computed(() =>
      paymentForm.value.payment_date &&
      paymentForm.value.payment_method &&
      paymentForm.value.amount > 0 &&
      paymentForm.value.amount <= remainingBalance.value
    );

    // Methods
    const fetchPaymentInfo = async () => {
      isLoading.value = true;
      error.value = null;

      try {
        // Fetch invoice details first
        const invoiceResponse = await axios.get(`/api/invoices/${invoiceId.value}`);
        invoice.value = invoiceResponse.data.data;

        // Then fetch payment information
        const paymentResponse = await axios.get(`/api/invoices/${invoiceId.value}/payment-info`);
        receivable.value = paymentResponse.data.data;

        // Get payment history if available
        if (receivable.value && receivable.value.receivablePayments) {
          payments.value = receivable.value.receivablePayments;
        }
      } catch (err) {
        console.error('Error fetching payment information:', err);
        error.value = 'Gagal memuat informasi pembayaran. Silakan coba lagi.';
      } finally {
        isLoading.value = false;
      }
    };

    const resetPaymentForm = () => {
      paymentForm.value = {
        payment_date: new Date().toISOString().split('T')[0],
        payment_method: '',
        reference_number: '',
        amount: 0,
        notes: ''
      };
      validationErrors.value = {};
    };

    const savePayment = async () => {
      isSubmitting.value = true;
      validationErrors.value = {};

      try {
        // In a real application, you would call your API here
        const response = await axios.post(`/api/receivable-payments`, {
          receivable_id: receivable.value.receivable_id,
          payment_date: paymentForm.value.payment_date,
          payment_method: paymentForm.value.payment_method,
          reference_number: paymentForm.value.reference_number,
          amount: paymentForm.value.amount,
          notes: paymentForm.value.notes
        });

        // Close modal and refresh data
        showRecordPaymentModal.value = false;
        resetPaymentForm();
        fetchPaymentInfo();

      } catch (err) {
        console.error('Error saving payment:', err);

        if (err.response && err.response.status === 422) {
          validationErrors.value = err.response.data.errors;
        } else {
          alert('Gagal menyimpan pembayaran. Silakan coba lagi.');
        }
      } finally {
        isSubmitting.value = false;
      }
    };

    const confirmVoidPayment = (payment) => {
      paymentToVoid.value = payment;
      showVoidConfirmation.value = true;
    };

    const cancelVoidPayment = () => {
      paymentToVoid.value = null;
      showVoidConfirmation.value = false;
    };

    const voidPayment = async () => {
      if (!paymentToVoid.value) return;

      try {
        // In a real application, you would call your API here
        await axios.post(`/api/receivable-payments/${paymentToVoid.value.payment_id}/void`);

        // Refresh data
        fetchPaymentInfo();
        showVoidConfirmation.value = false;
        paymentToVoid.value = null;

      } catch (err) {
        console.error('Error voiding payment:', err);
        alert('Gagal membatalkan pembayaran. Silakan coba lagi.');
      }
    };

    const printReceipt = () => {
      alert('Fitur cetak tanda terima sedang dalam pengembangan.');
    };

    const sendPaymentReminder = () => {
      alert('Fitur pengingat pembayaran sedang dalam pengembangan.');
    };

    const downloadStatement = () => {
      alert('Fitur unduh laporan pembayaran sedang dalam pengembangan.');
    };

    const viewInvoice = () => {
      router.push(`/sales/invoices/${invoiceId.value}`);
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

    const formatCurrency = (amount) => {
      if (amount === undefined || amount === null) return 'N/A';
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: invoice.value.currency_code || 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount);
    };

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

    const getPaymentStatusClass = (status) => {
      switch (status) {
        case 'Lunas': return 'status-badge status-paid';
        case 'Bayar Sebagian': return 'status-badge status-partial';
        case 'Terlambat': return 'status-badge status-overdue';
        case 'Belum Bayar': return 'status-badge status-unpaid';
        default: return 'status-badge';
      }
    };

    const getCurrentUserRole = () => {
      // In a real application, you would get this from your auth system
      // For now, we'll just return Admin so we can see the void payment option
      return 'Admin';
    };

    onMounted(() => {
      fetchPaymentInfo();
    });

    return {
      invoice,
      receivable,
      payments,
      isLoading,
      error,
      showRecordPaymentModal,
      showVoidConfirmation,
      paymentToVoid,
      isSubmitting,
      validationErrors,
      paymentForm,
      invoiceId,
      customerName,
      totalPaid,
      remainingBalance,
      paymentProgressPercent,
      getProgressColorClass,
      isOverdue,
      paymentStatus,
      canRecordPayment,
      canVoidPayment,
      isPaymentFormValid,
      fetchPaymentInfo,
      resetPaymentForm,
      savePayment,
      confirmVoidPayment,
      cancelVoidPayment,
      voidPayment,
      printReceipt,
      sendPaymentReminder,
      downloadStatement,
      viewInvoice,
      goBack,
      formatDate,
      formatCurrency,
      getStatusClass,
      getPaymentStatusClass
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

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
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

.btn-outline {
  background-color: transparent;
  border-color: var(--gray-300);
  color: var(--gray-700);
}

.btn-outline:hover {
  background-color: var(--gray-100);
}

.btn-danger {
  color: var(--danger-color);
}

.btn-danger:hover {
  background-color: rgba(220, 38, 38, 0.1);
}

.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
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

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
}

.summary-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 1rem;
  color: var(--gray-800);
  font-weight: 500;
}

.payment-status {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.status-item {
  display: flex;
  flex-direction: column;
}

.status-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-bottom: 0.5rem;
}

.status-value {
  font-size: 1rem;
  color: var(--gray-800);
  font-weight: 500;
}

.alert {
  padding: 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.alert-danger {
  background-color: #fee2e2;
  color: #b91c1c;
}

.progress-container {
  margin-top: 1.5rem;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.progress-bar {
  height: 0.75rem;
  background-color: var(--gray-200);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.3s ease;
}

.progress-success {
  background-color: #10b981;
}

.progress-warning {
  background-color: #f59e0b;
}

.progress-danger {
  background-color: #ef4444;
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

.text-right {
  text-align: right;
}

.text-danger {
  color: var(--danger-color);
}

.overdue-tag {
  display: inline-block;
  margin-left: 0.5rem;
  padding: 0.125rem 0.375rem;
  background-color: #fee2e2;
  color: #b91c1c;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.voided-tag {
  display: inline-block;
  padding: 0.125rem 0.375rem;
  background-color: var(--gray-200);
  color: var(--gray-600);
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.form-group {
  margin-bottom: 1.5rem;
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

.form-control.is-invalid {
  border-color: var(--danger-color);
}

.form-note {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: var(--gray-600);
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

.no-data {
  padding: 2rem;
  text-align: center;
  color: var(--gray-500);
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
  background-color: #fee2e2;
  color: #b91c1c;
}

.status-unpaid {
  background-color: #e0f2fe;
  color: #0369a1;
}

/* Modal Styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 50;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 500px;
  z-index: 60;
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: var(--gray-800);
}

.close-btn {
  background: none;
  border: none;
  color: var(--gray-500);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.25rem;
}

.close-btn:hover {
  background-color: var(--gray-100);
  color: var(--gray-700);
}

.modal-body {
  padding: 1.5rem;
}

@media (max-width: 768px) {
  .summary-grid,
  .payment-status {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    flex-direction: column;
  }

  .action-buttons .btn {
    width: 100%;
  }

  .data-table {
    font-size: 0.875rem;
  }

  .data-table th, .data-table td {
    padding: 0.5rem;
  }

  .modal-content {
    width: 90%;
    margin: 0 1rem;
  }
}
</style>
