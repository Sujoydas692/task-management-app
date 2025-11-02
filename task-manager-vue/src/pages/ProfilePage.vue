<script setup>
import { onMounted, ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../stores/authStore';

setActivePinia(createPinia());
const authStore = useAuthStore();
const user = ref({
  name: '',
  email: '',
  profile_image: ''
})

const profileImageFile = ref(null);
const previewImage = ref(null);
const fileInput = ref(null);

const fetchProfile = async () => {
  const data = await authStore.getProfile()
  if (data) user.value = data
}

onMounted(() => {
  fetchProfile();
});

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
    profileImageFile.value = file;
    previewImage.value = URL.createObjectURL(file);
  }
}

const onUpdate = async () => {
  const formData = new FormData()
  formData.append('name', user.value.name)
  if (profileImageFile.value) {
    formData.append('profile_image', profileImageFile.value)
  }

  const updatedUser = await authStore.updateProfile(formData)
  if (updatedUser) {
    user.value = updatedUser;
    previewImage.value = null;
     if (fileInput.value) fileInput.value.value = '';
  }
}

</script>
<template>
  <div class="content">
    <div class="container">
      <div class="row">
        <div class="col-md-12 col-lg-12">
          <div class="card animated fadeIn w-100 p-3">
            <div class="card-body">
              <h4>User Profile</h4>
              <hr />
              <div class="container-fluid m-0 p-0">
                <div class="row m-0 p-0">
                  <div class="col-md-8">
                    <div class="row">
                      <div class="col-md-4 p-2">
                        <h6>Email Address</h6>
                        <input
                          v-model="user.email"
                          placeholder="User Email"
                          class="form-control"
                          readonly
                          type="email"
                        />
                      </div>
                      <div class="col-md-4 p-2">
                        <h6>Full Name</h6>
                        <input
                          v-model="user.name"
                          placeholder="Full Name"
                          class="form-control"
                          type="text"
                        />
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="row">
                      <div class="col-md-8 p-2">
                        <h6>Profile Image</h6>
                        <input
                        ref="fileInput"
                          @change="onFileChange"
                          class="form-control"
                          type="file"
                        />
                      </div>
                      <div class="col-md-4 p-2">
                        <img
                          id="profileImage"
                          class="mt-2 rounded border"
                          :src="previewImage ? previewImage : (user.profile_image ? user.profile_image : 'https://t3.ftcdn.net/jpg/02/00/90/24/360_F_200902415_G4eZ9Ok3Ypd4SZZKjc8nqJyFVp1eOD6V.jpg')"
                          alt="profile Image"
                          width="150"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row m-0 p-0">
                  <div class="col-md-4 p-2">
                    <button
                      @click="onUpdate"
                      class="btn btn-sm btn-primary"
                    >
                      Update
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped></style>
