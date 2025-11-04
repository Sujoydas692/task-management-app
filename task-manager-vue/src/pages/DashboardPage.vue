<script setup>
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from "vue";
import Chart from "chart.js/auto";
import apiClient from "../services/axiosClient";
import { useAuthStore } from "../stores/authStore";

const authStore = useAuthStore();
const user = ref(authStore.user || {});
const adminChartCanvas = ref(null);
const userChartCanvas = ref(null);
const chartInstance = ref(null);

const adminStats = ref({});
const userStats = ref({});
const loading = ref(true);

onMounted(async () => {
  await loadDashboard();
});

watch(
  () => authStore.user,
  async (newUser) => {
    user.value = newUser;
    await loadDashboard();
  }
);

onBeforeUnmount(() => {
  destroyChart();
});

const loadDashboard = async () => {
  loading.value = true;
  destroyChart();

  try {
    if (user.value?.type === "Admin") {
      const res = await apiClient.get("/admin/dashboard-data");
      adminStats.value = res.data.data;

      // wait for DOM to update
      await nextTick();

      setTimeout(() => {
        renderAdminChart();
      }, 100);
    } else {
      const res = await apiClient.get("/user/dashboard-data");
      userStats.value = res.data.data;

      await nextTick();
      setTimeout(() => {
        renderUserChart();
      }, 100);
    }
  } catch (error) {
    console.error("Dashboard Load Error:", error);
  } finally {
    loading.value = false;
  }
};

// === ChartJS Helpers ===
const destroyChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
};

const renderAdminChart = () => {
  destroyChart();
  if (!adminChartCanvas.value) {
    console.warn("Admin chart canvas not found yet, retrying...");
    setTimeout(renderAdminChart, 100);
    return;
  }

  const ctx = adminChartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance.value = new Chart(ctx, {
    type: "bar",
    data: {
      labels: adminStats.value.taskLabels || [],
      datasets: [
        {
          label: "Tasks by Status",
          data: adminStats.value.taskCounts || [],
          backgroundColor: ["#17c1e8", "#0d6efd", "#ffc107", "#198754"],
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
    },
  });
};

const renderUserChart = () => {
  destroyChart();
  if (!userChartCanvas.value) {
    console.warn("User chart canvas not found yet, retrying...");
    setTimeout(renderUserChart, 100);
    return;
  }

  const ctx = userChartCanvas.value.getContext("2d");
  if (!ctx) return;

  chartInstance.value = new Chart(ctx, {
    type: "bar",
    data: {
      labels: userStats.value.projectLabels || [],
      datasets: [
        {
          label: "My Tasks",
          data: userStats.value.projectCounts || [],
          backgroundColor: ["#0d6efd", "#ffc107", "#198754"],
        },
      ],
    },
    options: { responsive: true },
  });
};
</script>

<template>
  <div class="content">
    <h3 class="mb-4">Dashboard</h3>

    <div v-if="loading" class="text-center py-5 text-muted">
      Loading dashboard...
    </div>

    <template v-else>
      <!-- 🔹 Admin Dashboard -->
      <div v-if="user.type === 'Admin'">
        <div class="row mb-4">
          <div
            class="col-12 col-sm-6 col-md-3"
            v-for="(val, key) in {
              'Total Users': adminStats.total_users,
              'Total Tasks': adminStats.total_tasks,
              'Total Groups': adminStats.total_groups,
              'Completed Tasks': adminStats.completed_tasks
            }"
            :key="key"
          >
            <div class="card text-center shadow-sm border-0">
              <div class="card-body">
                <h6 class="fw-bold">{{ key }}</h6>
                <h2 class="fw-bolder text-primary">{{ val ?? 0 }}</h2>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="mb-3 fw-semibold">📊 Task Status Overview</h5>
            <canvas ref="adminChartCanvas" height="120"></canvas>
          </div>
        </div>
      </div>

      <!-- 🔹 User Dashboard -->
      <div v-else>
        <div class="row mb-4">
          <div
            class="col-md-6"
            v-for="(val, key) in {
              'My Assigned Tasks': userStats.assigned_tasks,
              'Completed Tasks': userStats.completed_tasks
            }"
            :key="key"
          >
            <div class="card text-center shadow-sm border-0">
              <div class="card-body">
                <h6 class="fw-bold">{{ key }}</h6>
                <h2 class="fw-bolder text-success">{{ val ?? 0 }}</h2>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="mb-3 fw-semibold">📈 My Tasks Overview</h5>
            <canvas ref="userChartCanvas" height="120"></canvas>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.side-nav-open {
  width: 250px;
  height: 100vh;
  background: #f8f9fa;
  position: fixed;
  /* top: 56px; */
  left: 0;
  overflow-y: auto;
}
.content {
  margin-left: 250px;
  margin-top: 56px;
  padding: 20px;
}
.card {
  border-radius: 10px;
  transition: transform 0.2s ease;
}
.card:hover {
  transform: translateY(-3px);
}
.side-bar-item {
  display: flex;
  align-items: center;
  padding: 10px 20px;
  color: #333;
  text-decoration: none;
}
.side-bar-item:hover,
.side-bar-item-active {
  background-color: #e9ecef;
  font-weight: bold;
}
.side-bar-item-icon {
  margin-right: 10px;
}
.user-dropdown {
  position: relative;
}
.user-dropdown-content {
  display: none;
  position: absolute;
  right: 0;
  background-color: white;
  min-width: 200px;
  border: 1px solid #ddd;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}
.user-dropdown:hover .user-dropdown-content {
  display: block;
}
.icon-nav-img {
  width: 35px;
  height: 35px;
  border-radius: 50%;
}
.nav-logo {
  height: 35px;
}
</style>