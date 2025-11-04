<script setup>
import { onMounted, ref } from "vue";
import { useAuthStore } from "../stores/authStore";

const authStore = useAuthStore();
const users = ref([]);

const fetchAllUser = async () => {
  try {
    const allUsers = await authStore.getAllUsers();

    users.value = allUsers.filter(
      (user) => user.name !== "admin" && user.email !== "admin@example.com"
    );

    localStorage.setItem("users", JSON.stringify(users.value));
  } catch (error) {
    console.error("Error loading users:", error);
  }
};

onMounted(async () => {
  const cached = localStorage.getItem("users");
  if (cached) {
    users.value = JSON.parse(cached);
  }

  await fetchAllUser();
});

const getUserImage = (user) =>
  user.profile_image ||
  "https://t3.ftcdn.net/jpg/02/00/90/24/360_F_200902415_G4eZ9Ok3Ypd4SZZKjc8nqJyFVp1eOD6V.jpg";
</script>
<template>
  <div class="content">
    <div class="container">
      <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4>Users List</h4>
        </div>
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Profile Image</th>
              <th>Name</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>
                <div
                  class="position-relative"
                  style="width: 45px; height: 45px"
                >

                  <!-- Real image -->
                  <img
                    :src="getUserImage(user)"
                    alt="Profile"
                    class="icon-nav-img"
                    width="45"
                    height="45"
                    loading="lazy"
                  />
                </div>
              </td>

              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="2" class="text-center text-muted">
                No users found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
<style scoped>
.placeholder-glow {
  animation: shimmer 1.2s infinite linear;
  background: linear-gradient(to right, #f0f0f0 8%, #e0e0e0 18%, #f0f0f0 33%);
  background-size: 1000px 100%;
}
@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}
</style>
