<template>
  <el-dialog title="Send Email" :visible.sync="isVisible.value">
    <div class="">
      <div class="row">
        <div class="col-md-12 text-left">
          <div class="invoice-form-row form-group">
            <label for="recipient">To: </label>
            <input
              type="text"
              class="form-control"
              name="recipient"
              id="recipient"
              v-model="formData.recipients[0]"
            />
          </div>
        </div>

        <div class="col-md-12 text-left">
          <div class="invoice-form-row form-group">
            <label for="subject">Subject: </label>
            <input
              type="text"
              class="form-control"
              name="subject"
              id="subject"
              v-model="formData.subject"
            />
          </div>
        </div>

        <div class="col-md-12 text-left">
          <div class="invoide-form-row form-group">
            <label for="">Message</label>
            <textarea
              class="form-control"
              v-model="formData.message"
              cols="3"
              rows="3"
            >
            </textarea>
          </div>
        </div>
      </div>
    </div>

    <span slot="footer" class="dialog-footer">
      <el-button class="danger" @click="isVisible.value = false"
        >Cancel</el-button
      >
      <el-button type="primary" v-if="formData.id" @click="deletePayment"
        >Delete</el-button
      >
      <el-button type="success" v-else @click="sendEmail"
        >Send Email</el-button
      >
    </span>
  </el-dialog>
</template>

<script>
export default {
  props: {
    isVisible: Object,
    invoice: [Object, null],
    endpoint: {
      type: String,
      required: true
    }
  },

  data() {
    return {
      formData: {
        attach_pdf: false,
        message: "HI",
        recipients: ["jesusant.guerrero@gmail.com"],
        subject: "Invoice #1 from InCode"
      }
    };
  },

  watch: {
    invoice: {
      handler(invoice) {
        if (invoice && invoice.client) {
          this.formData.recipients = [invoice.client.email];
        }
      },
      deep: true,
      immediate: true
    }
  },

  methods: {
    sendEmail() {
      if (!this.formData.recipients[0]) {
        this.$notify({
          type: "error",
          message: "should specify a recipient"
        });
        return;
      }

      const formData = { ...this.formData };

      this.$http
        .post(this.endpoint, formData)
        .then(() => {
          this.$emit("sent");
          this.resetForm(true);
        })
        .catch(err => {
          this.$notify({
            type: "error",
            message: err.response
              ? err.response.data.status.message
              : "Ha ocurrido un error"
          });
        });
    },

    resetForm(shouldClose) {
      this.formData = {
        attach_pdf: false,
        message: "",
        recipients: [""],
        subject: ""
      };
      if (shouldClose) {
        this.isVisible.value = false;
      }
    }
  }
};
</script>
