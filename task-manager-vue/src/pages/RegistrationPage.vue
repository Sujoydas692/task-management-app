<script setup>
import { ref, watch } from "vue";
import { useAuthStore } from "../stores/authStore";
import { useRouter } from "vue-router";

const email = ref("");
const name = ref("");
const password = ref("");
const password_confirm = ref("");
const profile_image = ref(null);
const previewUrl = ref(null);

const authStore = useAuthStore();
const router = useRouter();

// Handle file input change
const onFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    profile_image.value = file;
    previewUrl.value = URL.createObjectURL(file);
  }
};

const register = async () => {
  const formData = new FormData();
  formData.append("name", name.value);
  formData.append("email", email.value);
  formData.append("password", password.value);
  formData.append("password_confirmation", password_confirm.value);
  if (profile_image.value) {
    formData.append("profile_image", profile_image.value);
  }

  const success = await authStore.register(formData);
  if (success) {
    router.push({ name: "login" });
  }
};
</script>

<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-10 center-screen">
        <div class="card animated fadeIn w-100 p-3">
          <div class="card-body">
            <h4>Sign Up</h4>
            <hr />
            <div class="container-fluid m-0 p-0">
              <form @submit.prevent="register">
                <div class="row m-0 p-0">
                  <div class="col-md-12 p-2">
                    <label>Name</label>
                    <input
                      placeholder="First Name"
                      class="form-control animated fadeInUp"
                      type="text"
                      name="name"
                      v-model="name"
                    />
                  </div>

                  <div class="col-md-12 p-2">
                    <label>Email Address</label>
                    <input
                      placeholder="User Email"
                      class="form-control animated fadeInUp"
                      type="email"
                      name="email"
                      v-model="email"
                    />
                  </div>

                  <div class="col-md-12 p-2">
                    <label>Password</label>
                    <input
                      placeholder="User Password"
                      class="form-control animated fadeInUp"
                      type="password"
                      name="password"
                      v-model="password"
                    />
                  </div>

                  <div class="col-md-12 p-2">
                    <label>Confirm Password</label>
                    <input
                      placeholder="Confirm Password"
                      class="form-control animated fadeInUp"
                      type="password"
                      name="password_confirmation"
                      v-model="password_confirm"
                    />
                  </div>

                  <div class="col-md-12 p-2">
                    <label>Profile Image</label>
                    <input
                      type="file"
                      class="form-control"
                      name="profile_image"
                      @change="onFileChange"
                    />
                  </div>

                  <div v-if="previewUrl" class="col-md-12 p-2">
                    <img
                      :src="previewUrl"
                      alt="Preview"
                      class="mt-2 rounded border"
                      style="max-width: 120px"
                    />
                  </div>
                </div>
                <div class="row mt-2 p-0">
                  <div class="col-md-4 p-2">
                    <button
                      type="submit"
                      class="btn mt-3 w-100 float-end btn-primary animated fadeInUp"
                    >
                      Sign Up
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
