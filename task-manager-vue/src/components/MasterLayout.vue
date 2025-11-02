<script setup>
import { onMounted } from "vue";
import { useAuthStore } from "../stores/authStore";

const authStore = useAuthStore();

onMounted(() => {
  authStore.getProfile();
});
</script>

<template>
  <!-- Navbar -->
  <nav
      class="navbar navbar-expand-lg navbar-light bg-light fixed-top px-3 shadow-sm"
  >
    <div class="container-fluid">
      <RouterLink :to="{ name: 'dashboard' }" class="navbar-brand d-flex align-items-center">
        <i class="bi bi-list h4 m-0"></i>
        <img
            src="https://cdn-icons-png.flaticon.com/256/906/906334.png"
            class="nav-logo mx-2"
            alt="logo"
        />
      </RouterLink>
      <div class="d-flex align-items-center">
        <div class="user-dropdown">
          <img
            :src="authStore.user?.profile_image || 'https://t3.ftcdn.net/jpg/02/00/90/24/360_F_200902415_G4eZ9Ok3Ypd4SZZKjc8nqJyFVp1eOD6V.jpg'"
            alt="User"
            class="icon-nav-img"
          />
          <div class="user-dropdown-content p-3">
            <div class="text-center">
             <img
                :src="authStore.user?.profile_image || 'https://t3.ftcdn.net/jpg/02/00/90/24/360_F_200902415_G4eZ9Ok3Ypd4SZZKjc8nqJyFVp1eOD6V.jpg'"
                class="icon-nav-img mb-2"
                alt=""
              />
              <h6>{{ authStore.user?.name || 'Guest User' }}</h6>
              <hr class="p-0" />
            </div>
            <RouterLink :to="{ name: 'profile' }" class="side-bar-item">
              <i class="bi bi-person side-bar-item-icon"></i>
              <span class="side-bar-item-caption">Profile</span>
            </RouterLink>
            <span
                @click="authStore.logout"
                class="side-bar-item"
                style="cursor: pointer"
            >
              <i class="bi bi-box-arrow-right side-bar-item-icon"></i>
              <span class="side-bar-item-caption">Logout</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <div class="side-nav-open">
    <RouterLink :to="{ name: 'dashboard' }" class="side-bar-item mt-2" active-class="active-link">
      <i class="bi bi-speedometer2 side-bar-item-icon"></i>
      <span>Dashboard</span>
    </RouterLink>
    <RouterLink :to="{ name: 'tasks' }" class="side-bar-item mt-2" active-class="active-link">
      <i class="bi bi-pencil-square side-bar-item-icon"></i>
      <span>Tasks</span>
    </RouterLink>
    <RouterLink :to="{ name: 'groups' }" class="side-bar-item mt-2" active-class="active-link">
      <i class="bi bi-pencil-square side-bar-item-icon"></i>
      <span>Groups</span>
    </RouterLink>
    <a href="/Completed" class="side-bar-item mt-2" active-class="active-link">
      <i class="bi bi-check-circle side-bar-item-icon"></i>
      <span>Completed</span>
    </a>
    <a href="/Canceled" class="side-bar-item mt-2" active-class="active-link">
      <i class="bi bi-x-octagon side-bar-item-icon"></i>
      <span>Canceled</span>
    </a>
  </div>

  <RouterView/>
</template>

<style scoped>

.side-bar-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  color: #333;
  text-decoration: none;
  border-radius: 6px;
  transition: 0.2s;
}

.side-bar-item:hover {
  background-color: #CCCCCC #F5F5F5;
  text-decoration: none;
}

.active-link {
  background-color: #cb0c9e28;
  color: #cb0c9f;
  font-weight: 600;
  border-left: 4px solid #cb0c9f;
}

</style>